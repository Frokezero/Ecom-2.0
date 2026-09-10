/* Progressive AJAX navigation for same-origin pages. */
(() => {
    'use strict';

    let activeRequest = null;

    function contentSelector(doc = document) {
        return doc.body.classList.contains('admin-body') ? '.admin-main' : '#main-content';
    }

    function canNavigate(url) {
        return url.origin === location.origin && !url.pathname.includes('/api/');
    }

    function setLoading(loading) {
        document.documentElement.classList.toggle('ajax-navigating', loading);
        const main = document.querySelector(contentSelector());
        if (main) main.setAttribute('aria-busy', String(loading));
    }

    function exposePageFunctions(source) {
        return source.replace(
            /(^|[;}\n])\s*(async\s+)?function\s+([A-Za-z_$][\w$]*)\s*\(/g,
            (_, prefix, asyncKeyword = '', name) => `${prefix} window.${name}=${asyncKeyword}function ${name}(`
        );
    }

    async function runPageScripts(container) {
        const scripts = Array.from(container.querySelectorAll('script'));
        for (const oldScript of scripts) {
            oldScript.remove();
            if (oldScript.src) {
                const src = new URL(oldScript.src, location.href).href;
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = src;
                    script.onload = resolve;
                    script.onerror = reject;
                    document.body.appendChild(script);
                });
                continue;
            }
            if (!oldScript.textContent.trim() || oldScript.type === 'application/ld+json') continue;
            const script = document.createElement('script');
            const nonceSource = document.querySelector('script[nonce]');
            if (nonceSource) script.nonce = nonceSource.nonce;
            script.textContent = `{${exposePageFunctions(oldScript.textContent)}}`;
            document.body.appendChild(script);
            script.remove();
        }
    }

    function updateNavigationState(url) {
        document.querySelectorAll('.site-nav a, .mobile-bottom-nav a, .admin-nav a').forEach(link => {
            const linkUrl = new URL(link.href, location.href);
            link.classList.toggle('active', linkUrl.pathname === url.pathname && linkUrl.search === url.search);
        });
        document.getElementById('siteNav')?.classList.remove('open');
        document.getElementById('adminSidebar')?.classList.remove('open');
        document.body.classList.remove('admin-menu-open', 'filters-open');
    }

    async function navigate(target, options = {}) {
        const url = new URL(target, location.href);
        if (!canNavigate(url)) {
            location.assign(url.href);
            return;
        }

        if (activeRequest) activeRequest.abort();
        const request = new AbortController();
        activeRequest = request;
        setLoading(true);

        try {
            const response = await fetch(url.href, {
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                signal: request.signal
            });
            if (!response.ok) throw new Error(`Navigation failed: ${response.status}`);

            const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const currentSelector = contentSelector();
            if (currentSelector !== contentSelector(nextDocument)) throw new Error('Layout changed');
            const currentMain = document.querySelector(currentSelector);
            const nextMain = nextDocument.querySelector(currentSelector);
            if (!currentMain || !nextMain) throw new Error('Page content is missing');

            currentMain.innerHTML = nextMain.innerHTML;
            document.title = nextDocument.title;
            if (!options.history) history.pushState({ajaxNavigation: true}, '', url.href);
            updateNavigationState(url);
            await runPageScripts(currentMain);

            const hashTarget = url.hash && document.querySelector(url.hash);
            if (hashTarget) hashTarget.scrollIntoView();
            else window.scrollTo({top: 0, behavior: 'instant'});
            currentMain.focus({preventScroll: true});
            document.dispatchEvent(new CustomEvent('ajax:page-loaded', {detail: {url: url.href}}));
        } catch (error) {
            if (error.name !== 'AbortError') location.assign(url.href);
        } finally {
            if (activeRequest === request) {
                activeRequest = null;
                setLoading(false);
            }
        }
    }

    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('a[href]');
        if (!link || link.target || link.hasAttribute('download') || link.dataset.noAjax !== undefined) return;
        const url = new URL(link.href, location.href);
        if (!canNavigate(url)) return;
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
        event.preventDefault();
        navigate(url.href);
    });

    document.addEventListener('submit', async event => {
        if (event.defaultPrevented) return;
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.noAjax !== undefined || form.target) return;
        const explicitAction=form.getAttribute('action');
        const url = new URL(explicitAction || location.href, location.href);
        if (!canNavigate(url)) return;
        const formData = event.submitter ? new FormData(form, event.submitter) : new FormData(form);
        if(form.method.toUpperCase()==='GET'){
            event.preventDefault();url.search=new URLSearchParams(formData).toString();navigate(url.href);return;
        }
        if(form.method.toUpperCase()!=='POST')return;
        event.preventDefault();setLoading(true);
        try{
            const response=await fetch(url.href,{method:'POST',body:formData,headers:{'X-Requested-With':'XMLHttpRequest'}});
            const type=response.headers.get('content-type')||'';
            if(!response.ok||!type.includes('text/html'))throw new Error(`Form failed: ${response.status}`);
            const nextDocument=new DOMParser().parseFromString(await response.text(),'text/html'),selector=contentSelector(),current=document.querySelector(selector),next=nextDocument.querySelector(selector);
            if(!current||!next)throw new Error('Page content is missing');current.innerHTML=next.innerHTML;document.title=nextDocument.title;history.replaceState({ajaxNavigation:true},'',response.url||url.href);updateNavigationState(new URL(response.url||url.href));await runPageScripts(current);document.dispatchEvent(new CustomEvent('ajax:page-loaded',{detail:{url:response.url||url.href}}));window.scrollTo({top:0,behavior:'smooth'});
        }catch(error){location.assign(url.href)}finally{setLoading(false)}
    });

    window.addEventListener('popstate', () => navigate(location.href, {history: true}));
    function enhanceAddressSelection(){document.querySelectorAll('select[onchange*="address_id"]').forEach(select=>{select.onchange=null;select.removeAttribute('onchange');select.addEventListener('change',()=>navigate(`${location.pathname}?address_id=${encodeURIComponent(select.value)}`))})}
    document.addEventListener('DOMContentLoaded',enhanceAddressSelection);document.addEventListener('ajax:page-loaded',enhanceAddressSelection);
    window.ajaxNavigate = navigate;
})();
