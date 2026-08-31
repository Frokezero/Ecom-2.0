<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/database.php';$db=(new Database())->getConnection();if(!$db){fwrite(STDERR,"Database unavailable\n");exit(1);}
$db->exec("CREATE TABLE IF NOT EXISTS schema_migrations(version VARCHAR(100) PRIMARY KEY,checksum CHAR(64) NOT NULL,applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$files=glob(__DIR__.'/../database/migrations/*.sql');sort($files,SORT_NATURAL);
$baseline=in_array('--baseline',$argv,true);
foreach($files as $file){$version=basename($file);$sql=file_get_contents($file);$checksum=hash('sha256',$sql);$stmt=$db->prepare('SELECT checksum FROM schema_migrations WHERE version=?');$stmt->execute([$version]);$old=$stmt->fetchColumn();if($old!==false){if(!hash_equals((string)$old,$checksum)){fwrite(STDERR,"Checksum changed: $version\n");exit(2);}continue;}if($baseline&&strnatcmp($version,'018_')<0){$db->prepare('INSERT INTO schema_migrations(version,checksum) VALUES(?,?)')->execute([$version,$checksum]);echo "Baselined $version\n";continue;}try{$db->exec($sql);$db->prepare('INSERT INTO schema_migrations(version,checksum) VALUES(?,?)')->execute([$version,$checksum]);echo "Applied $version\n";}catch(Throwable $e){fwrite(STDERR,"Failed $version: ".$e->getMessage()."\n");exit(1);}}
