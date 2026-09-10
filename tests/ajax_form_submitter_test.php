<?php
$source = file_get_contents(dirname(__DIR__) . '/assets/js/navigation.js');
if (!str_contains($source, 'new FormData(form, event.submitter)')) {
    throw new RuntimeException('AJAX navigation does not preserve the clicked submit button');
}
if (!str_contains($source, 'body:formData')) {
    throw new RuntimeException('AJAX POST does not send the submitter-aware FormData');
}
echo "AJAX form submitter tests passed\n";
