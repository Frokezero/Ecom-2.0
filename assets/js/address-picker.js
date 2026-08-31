(() => {
  const source = 'https://raw.githubusercontent.com/thailand-geography-data/thailand-geography-json/main/src/geography.json';
  let dataPromise;
  const loadData = () => dataPromise || (dataPromise = (async () => {
    try {
      const cached = localStorage.getItem('kitchenmart_thai_addresses');
      if (cached) return JSON.parse(cached);
    } catch (_) { /* use network copy */ }
    const response = await fetch(source, { cache: 'force-cache' });
    if (!response.ok) throw new Error('โหลดข้อมูลที่อยู่ไม่สำเร็จ');
    const result = await response.json();
    try { localStorage.setItem('kitchenmart_thai_addresses', JSON.stringify(result)); } catch (_) { /* storage is optional */ }
    return result;
  })());

  const option = (value, label) => {
    const item = document.createElement('option');
    item.value = value;
    item.textContent = label;
    return item;
  };
  const fill = (select, items, valueKey, labelKey, placeholder) => {
    select.replaceChildren(option('', placeholder));
    items.forEach(item => select.append(option(String(item[valueKey]), item[labelKey])));
    select.disabled = items.length === 0;
  };

  function attach(textarea) {
    if (textarea.dataset.addressReady) return;
    textarea.dataset.addressReady = '1';
    textarea.required = false;
    textarea.hidden = true;
    const wrapper = document.createElement('div');
    wrapper.className = 'thai-address-picker';
    wrapper.innerHTML = '<label>บ้านเลขที่ / อาคาร / ถนน<input type="text" data-address-detail maxlength="500" placeholder="เช่น 99/9 ถนนสุขุมวิท"></label><div class="thai-address-grid"><label>จังหวัด<select data-address-province required><option value="">กำลังโหลดจังหวัด...</option></select></label><label>อำเภอ / เขต<select data-address-district required disabled><option value="">เลือกจังหวัดก่อน</option></select></label><label>ตำบล / แขวง<select data-address-subdistrict required disabled><option value="">เลือกอำเภอก่อน</option></select></label><label>รหัสไปรษณีย์<input data-address-postal inputmode="numeric" pattern="[0-9]{5}" maxlength="5" required placeholder="เช่น 47000"></label></div><small class="thai-address-status" aria-live="polite">เลือกพื้นที่เพื่อเติมที่อยู่ให้ครบอัตโนมัติ</small>';
    textarea.after(wrapper);
    const detail = wrapper.querySelector('[data-address-detail]');
    const province = wrapper.querySelector('[data-address-province]');
    const district = wrapper.querySelector('[data-address-district]');
    const subdistrict = wrapper.querySelector('[data-address-subdistrict]');
    const postal = wrapper.querySelector('[data-address-postal]');
    const status = wrapper.querySelector('.thai-address-status');
    let syncingPostal = false;
    detail.value = textarea.value.trim().replace(/\s*(ตำบล|แขวง|อำเภอ|เขต|จังหวัด)\s+.*$/u, '').trim();
    let rows = [];
    const compose = () => {
      const p = province.selectedOptions[0]?.textContent || '';
      const d = district.selectedOptions[0]?.textContent || '';
      const s = subdistrict.selectedOptions[0]?.textContent || '';
      const parts = [detail.value.trim(), s ? `ตำบล/แขวง ${s}` : '', d ? `อำเภอ/เขต ${d}` : '', p ? `จังหวัด ${p}` : '', postal.value.trim()].filter(Boolean);
      textarea.value = parts.join(' ');
    };
    const lookupPostal = () => {
      const code = postal.value.trim();
      if (!/^\d{5}$/.test(code)) return;
      const found = rows.find(row => String(row.postalCode).padStart(5, '0') === code);
      if (!found) { status.textContent = 'ไม่พบรหัสไปรษณีย์นี้ในฐานข้อมูล'; return; }
      province.value = String(found.provinceCode);
      syncingPostal = true;
      province.dispatchEvent(new Event('change'));
      setTimeout(() => {
        district.value = String(found.districtCode);
        district.dispatchEvent(new Event('change'));
        setTimeout(() => { subdistrict.value = String(found.subdistrictCode); subdistrict.dispatchEvent(new Event('change')); postal.value = code; syncingPostal = false; compose(); }, 0);
      }, 0);
    };
    loadData().then(input => {
      rows = input;
      const provinces = [...new Map(rows.map(row => [row.provinceCode, row])).values()].sort((a, b) => a.provinceNameTh.localeCompare(b.provinceNameTh, 'th'));
      fill(province, provinces, 'provinceCode', 'provinceNameTh', 'เลือกจังหวัด');
      status.textContent = 'พร้อมเลือกจังหวัด อำเภอ ตำบล และรหัสไปรษณีย์';
      const savedPostal=(textarea.value.match(/\b\d{5}\b/)||[])[0];
      if(savedPostal){postal.value=savedPostal;lookupPostal();const savedDetail=textarea.value.split(/ตำบล\/แขวง|แขวง|ตำบล/u)[0].trim();if(savedDetail)detail.value=savedDetail;}
    }).catch(() => { status.textContent = 'ไม่สามารถโหลดข้อมูลที่อยู่ได้ กรุณาลองใหม่'; });
    province.addEventListener('change', () => {
      const code = province.value;
      const districts = [...new Map(rows.filter(row => String(row.provinceCode) === code).map(row => [row.districtCode, row])).values()].sort((a, b) => a.districtNameTh.localeCompare(b.districtNameTh, 'th'));
      fill(district, districts, 'districtCode', 'districtNameTh', 'เลือกอำเภอ / เขต');
      fill(subdistrict, [], 'subdistrictCode', 'subdistrictNameTh', 'เลือกตำบล / แขวง');
      if (!syncingPostal) postal.value = ''; compose();
    });
    district.addEventListener('change', () => {
      const code = district.value;
      const subs = rows.filter(row => String(row.districtCode) === code && String(row.provinceCode) === province.value).sort((a, b) => a.subdistrictNameTh.localeCompare(b.subdistrictNameTh, 'th'));
      fill(subdistrict, subs, 'subdistrictCode', 'subdistrictNameTh', 'เลือกตำบล / แขวง');
      if (!syncingPostal) postal.value = ''; compose();
    });
    subdistrict.addEventListener('change', () => {
      const found = rows.find(row => String(row.subdistrictCode) === subdistrict.value);
      if (found) postal.value = String(found.postalCode).padStart(5, '0');
      compose();
    });
    postal.addEventListener('input', lookupPostal);
    detail.addEventListener('input', compose);
    textarea.closest('form')?.addEventListener('submit', compose);
  }

  window.initThaiAddressPickers=()=>document.querySelectorAll('textarea[data-address-picker], textarea[name="address"], textarea[name="shipping_address"], textarea[name="return_address"]').forEach(attach);
  document.addEventListener('DOMContentLoaded', window.initThaiAddressPickers);
})();
