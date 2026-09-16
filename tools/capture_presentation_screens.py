from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'presentation' / 'screenshots'
OUT.mkdir(parents=True, exist_ok=True)
BASE = 'http://127.0.0.1:8000/'
CHROME = r'C:\Program Files\Google\Chrome\Application\chrome.exe'

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path=CHROME)
    page = browser.new_page(viewport={'width': 1440, 'height': 900}, device_scale_factor=1)
    for name, path in [('home','index.php'),('login','login.php'),('seller-register','seller-register.php')]:
        page.goto(BASE + path, wait_until='domcontentloaded', timeout=30000)
        page.wait_for_timeout(1200)
        if name == 'home':
            page.screenshot(path=str(OUT / 'home-promo.png'), full_page=False)
        if name == 'home' and page.locator('.floating-promo-close').count():
            page.locator('.floating-promo-close').click()
        page.screenshot(path=str(OUT / f'{name}.png'), full_page=False)
        print(name, page.title(), page.url)
    browser.close()
