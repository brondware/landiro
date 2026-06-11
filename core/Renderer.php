<?php
class Renderer {
    public function renderLanding(array $landing, bool $adminMode = false): string {
        $gs = $landing['global_styles'] ?? [];
        $seo = $landing['seo'] ?? [];
        $scripts = $landing['scripts'] ?? [];

        $title = htmlspecialchars($seo['title'] ?? $landing['title'] ?? 'Landing');
        $desc = htmlspecialchars($seo['description'] ?? '');
        $ogImage = htmlspecialchars($seo['og_image'] ?? '');
        $favicon = htmlspecialchars($seo['favicon'] ?? '');

        $globalCssVars = '';
        if ($gs) {
            $globalCssVars .= ':root {';
            if (!empty($gs['primary_color']))   $globalCssVars .= '--primary-color:' . $gs['primary_color'] . ';';
            if (!empty($gs['secondary_color'])) $globalCssVars .= '--secondary-color:' . $gs['secondary_color'] . ';';
            if (!empty($gs['accent_color']))    $globalCssVars .= '--accent-color:' . $gs['accent_color'] . ';';
            if (!empty($gs['text_color']))      $globalCssVars .= '--text-color:' . $gs['text_color'] . ';';
            if (!empty($gs['font_family']))     $globalCssVars .= '--font-family:' . $gs['font_family'] . ';';
            $globalCssVars .= '}';
            if (!empty($gs['font_family']) && strpos($gs['font_family'], 'Google') === false) {
                $fontName = explode(',', $gs['font_family'])[0];
                $fontName = trim(str_replace('"', '', $fontName));
                if (!in_array($fontName, ['Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana', 'sans-serif', 'serif'])) {
                    $globalCssVars = '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n"
                        . '<link href="https://fonts.googleapis.com/css2?family=' . urlencode($fontName) . ':wght@400;600;700&display=swap" rel="stylesheet">' . "\n"
                        . '<style>' . $globalCssVars;
                    $globalCssVars .= 'body{font-family:var(--font-family,sans-serif);}';
                    $globalCssVars .= '</style>';
                } else {
                    $globalCssVars = '<style>' . $globalCssVars . 'body{font-family:var(--font-family,sans-serif);}</style>';
                }
            } else {
                $globalCssVars = '<style>' . $globalCssVars . 'body{font-family:var(--font-family,sans-serif);}</style>';
            }
        }

        // GTM
        $gtmHead = $gtmBody = '';
        if (!empty($scripts['gtm_id'])) {
            $gtmId = htmlspecialchars($scripts['gtm_id']);
            $gtmHead = "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$gtmId}');</script>";
            $gtmBody = "<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$gtmId}\" height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>";
        }

        $gaScript = '';
        if (!empty($scripts['ga_id'])) {
            $gaId = htmlspecialchars($scripts['ga_id']);
            $gaScript = "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$gaId}\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$gaId}');</script>";
        }

        $fbPixel = '';
        if (!empty($scripts['fb_pixel'])) {
            $pixelId = htmlspecialchars($scripts['fb_pixel']);
            $fbPixel = "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{$pixelId}');fbq('track','PageView');</script>";
        }

        $ttPixel = '';
        if (!empty($scripts['tt_pixel'])) {
            $ttId = htmlspecialchars($scripts['tt_pixel']);
            $ttPixel = "<script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript';o.async=!0;o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};ttq.load('{$ttId}');ttq.page()}(window,document,'ttq');</script>";
        }

        $snapPixel = '';
        if (!empty($scripts['snap_pixel'])) {
            $snapId = htmlspecialchars($scripts['snap_pixel']);
            $snapPixel = "<script>(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function(){a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};a.queue=[];var s='script';r=t.createElement(s);r.async=!0;r.src=n;var u=t.getElementsByTagName(s)[0];u.parentNode.insertBefore(r,u)})(window,document,'https://sc-static.net/scevent.min.js');snaptr('init','{$snapId}');snaptr('track','PAGE_VIEW');</script>";
        }

        $customCss = !empty($gs['custom_css']) ? '<style>' . $gs['custom_css'] . '</style>' : '';
        $headScripts = $scripts['head'] ?? '';
        $bodyEndScripts = $scripts['body_end'] ?? '';

        // UTM tracking script (captures URL params → sessionStorage)
        $utmScript = '<script>(function(){var p=new URLSearchParams(location.search),u={};["utm_source","utm_medium","utm_campaign","utm_content","utm_term"].forEach(function(k){if(p.get(k))u[k]=p.get(k);});if(Object.keys(u).length)try{sessionStorage.setItem("_cms_utms",JSON.stringify(u));}catch(e){}})();</script>';

        // Success URL (post-submit redirect)
        $successUrlScript = '';
        if (!empty($landing['success_url'])) {
            $successUrlScript = '<script>window._CMS_SUCCESS_URL=' . json_encode($landing['success_url']) . ';</script>';
        }

        // Popup
        $popupHtml = '';
        if (!empty($landing['popup']['enabled']) && !$adminMode) {
            $pop     = $landing['popup'];
            $trigger = $pop['trigger'] ?? 'delay';
            $delay   = max(1, (int)($pop['delay'] ?? 5));
            $popContent = $pop['html'] ?? '';
            $popCss  = !empty($pop['css']) ? '<style>' . $pop['css'] . '</style>' : '';
            $triggerJs = $trigger === 'exit'
                ? "document.addEventListener('mouseleave',function(e){if(e.clientY<=0)show();});"
                : "setTimeout(show,{$delay}000);";
            $popupHtml = <<<HTML
{$popCss}
<div id="cms-popup-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px">
  <div style="position:relative;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;background:#fff;border-radius:16px;padding:32px 24px">
    <button onclick="document.getElementById('cms-popup-overlay').style.display='none'" style="position:absolute;top:10px;right:14px;border:none;background:none;font-size:24px;line-height:1;cursor:pointer;color:#94a3b8" aria-label="Закрити">×</button>
    {$popContent}
  </div>
</div>
<script>(function(){var shown=false;function show(){if(shown)return;shown=true;var el=document.getElementById('cms-popup-overlay');if(el)el.style.display='flex';}document.addEventListener('keydown',function(e){if(e.key==='Escape')document.getElementById('cms-popup-overlay').style.display='none';});{$triggerJs}})();</script>
HTML;
        }

        // Floating contact widget
        $floatingWidget = '';
        if (!empty($landing['floating_widget']['enabled']) && !$adminMode) {
            $fw  = $landing['floating_widget'];
            $pos = ($fw['position'] ?? 'right') === 'left' ? 'left:16px' : 'right:16px';
            $btns = '';
            $defs = [
                'whatsapp' => ['url' => 'https://wa.me/{v}',          'color' => '#25D366', 'icon' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>'],
                'viber'    => ['url' => 'viber://chat?number={v}',     'color' => '#7360F2', 'icon' => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/><path d="M8 10h1.5v1.5H8zm3.25 0h1.5v1.5h-1.5zm3.25 0h1.5v1.5H14.5z"/>'],
                'telegram' => ['url' => 'https://t.me/{v}',           'color' => '#2AABEE', 'icon' => '<path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4 20-7z"/>'],
                'phone'    => ['url' => 'tel:{v}',                     'color' => '#FF5A1F', 'icon' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>'],
            ];
            foreach (['whatsapp','viber','telegram','phone'] as $type) {
                $val = trim($fw[$type] ?? '');
                if (!$val) continue;
                $d = $defs[$type];
                // WhatsApp/Viber/Phone — strip non-digits for number
                $urlVal = in_array($type, ['whatsapp','viber','phone'])
                    ? preg_replace('/[^0-9+]/', '', $val)
                    : $val;
                $href  = str_replace('{v}', $urlVal, $d['url']);
                $label = match($type) {
                    'whatsapp' => 'WhatsApp', 'viber' => 'Viber',
                    'telegram' => 'Telegram', 'phone'  => htmlspecialchars($val),
                };
                $btns .= "<a href=\"{$href}\" target=\"_blank\" rel=\"noopener\" class=\"fw-btn\" style=\"background:{$d['color']}\" aria-label=\"{$label}\" title=\"{$label}\">"
                    . "<svg width=\"22\" height=\"22\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"#fff\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">{$d['icon']}</svg>"
                    . "</a>";
            }
            if ($btns) {
                $floatingWidget = <<<HTML
<style>
#cms-fw{position:fixed;bottom:72px;{$pos};display:flex;flex-direction:column;gap:10px;z-index:9996}
.fw-btn{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,.25);transition:transform .15s,box-shadow .15s;text-decoration:none}
.fw-btn:hover{transform:scale(1.1);box-shadow:0 6px 20px rgba(0,0,0,.3)}
</style>
<div id="cms-fw">{$btns}</div>
HTML;
            }
        }

        // Sticky bottom bar
        $stickyBar = '';
        if (!empty($landing['sticky_bar']['enabled']) && !$adminMode) {
            $bar      = $landing['sticky_bar'];
            $bg       = htmlspecialchars($bar['bg_color'] ?? '#FF5A1F');
            $fg       = htmlspecialchars($bar['text_color'] ?? '#ffffff');
            $text     = htmlspecialchars($bar['text'] ?? '');
            $btnText  = htmlspecialchars($bar['button_text'] ?? 'Замовити');
            $phone    = htmlspecialchars($bar['phone'] ?? '');
            $btnLink  = htmlspecialchars($bar['button_link'] ?? '#orderForm');
            $leftHtml = $phone
                ? "<a href=\"tel:{$phone}\" style=\"color:{$fg};text-decoration:none;display:flex;align-items:center;gap:8px;font-size:14px\"><svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><path d=\"M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z\"/></svg>{$text}</a>"
                : "<span style=\"font-size:14px;font-weight:600\">{$text}</span>";
            $stickyBar = <<<HTML
<div id="cms-sticky-bar" style="position:fixed;bottom:0;left:0;right:0;background:{$bg};color:{$fg};z-index:9997;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;box-shadow:0 -2px 16px rgba(0,0,0,.2)">
  {$leftHtml}
  <a href="{$btnLink}" style="background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.4);color:{$fg};text-decoration:none;padding:8px 20px;border-radius:99px;font-weight:700;font-size:14px;white-space:nowrap;flex-shrink:0">{$btnText}</a>
</div>
<div style="height:56px"></div>
HTML;
        }

        // Countdown Timer
        $countdownHtml = '';
        if (!empty($landing['countdown']['enabled']) && !$adminMode) {
            $cd      = $landing['countdown'];
            $bgColor = htmlspecialchars($cd['bg_color'] ?? '#1e293b');
            $fgColor = htmlspecialchars($cd['text_color'] ?? '#ffffff');
            $before  = htmlspecialchars($cd['label_before'] ?? 'Акція закінчується через:');
            $expired = htmlspecialchars($cd['label_expired'] ?? 'Акція завершена');
            $type    = ($cd['type'] ?? 'session') === 'fixed' ? 'fixed' : 'session';

            if ($type === 'fixed') {
                $endTs = strtotime($cd['end_date'] ?? '') ?: (time() + 3600);
                $initJs = "var _cdEnd={$endTs}000;";
            } else {
                $totalSec = (int)($cd['hours'] ?? 0) * 3600 + (int)($cd['minutes'] ?? 30) * 60 + (int)($cd['seconds'] ?? 0);
                if ($totalSec < 1) $totalSec = 1800;
                $initJs = "var _cdKey='_cd_end_{$landing['slug']}';var _cdEnd=parseInt(sessionStorage.getItem(_cdKey))||0;if(!_cdEnd){_cdEnd=Date.now()+{$totalSec}000;sessionStorage.setItem(_cdKey,_cdEnd);}";
            }

            $countdownHtml = <<<HTML
<div id="cms-countdown" style="background:{$bgColor};color:{$fgColor};text-align:center;padding:10px 16px;font-family:inherit;position:sticky;top:0;z-index:9998;width:100%">
  <span id="cms-cd-label" style="font-size:14px;font-weight:600;margin-right:10px">{$before}</span>
  <span id="cms-cd-timer" style="font-size:18px;font-weight:800;letter-spacing:2px;font-variant-numeric:tabular-nums">--:--:--</span>
</div>
<script>
(function(){
{$initJs}
var el=document.getElementById('cms-cd-timer');
var lb=document.getElementById('cms-cd-label');
var exp='{$expired}';
function pad(n){return String(n).padStart(2,'0');}
function tick(){
  var diff=Math.max(0,Math.floor((_cdEnd-Date.now())/1000));
  if(!diff){el.textContent='';lb.textContent=exp;return;}
  var h=Math.floor(diff/3600),m=Math.floor((diff%3600)/60),s=diff%60;
  el.textContent=(h?pad(h)+':':'')+pad(m)+':'+pad(s);
  setTimeout(tick,1000);
}
tick();
})();
</script>
HTML;
        }

        // Social Proof Ticker
        $socialProofHtml = '';
        if (!empty($landing['social_proof']['enabled']) && !$adminMode) {
            $sp       = $landing['social_proof'];
            $pos      = ($sp['position'] ?? 'bottom-left') === 'bottom-right' ? 'right:16px' : 'left:16px';
            $delay    = max(1, (int)($sp['delay'] ?? 5)) * 1000;
            $interval = max(3, (int)($sp['interval'] ?? 15)) * 1000;
            $duration = max(2, (int)($sp['duration'] ?? 4)) * 1000;
            $entries  = json_encode($sp['entries'] ?? [], JSON_UNESCAPED_UNICODE);

            $socialProofHtml = <<<HTML
<div id="cms-sp" style="position:fixed;bottom:16px;{$pos};z-index:9995;max-width:280px;display:none">
  <div style="background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.15);padding:12px 14px;display:flex;align-items:center;gap:10px;animation:cmsSpIn .35s ease">
    <div style="width:38px;height:38px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    </div>
    <div style="min-width:0">
      <div id="cms-sp-name" style="font-size:13px;font-weight:700;color:#1e293b"></div>
      <div id="cms-sp-desc" style="font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
      <div id="cms-sp-time" style="font-size:11px;color:#94a3b8;margin-top:2px"></div>
    </div>
  </div>
</div>
<style>@keyframes cmsSpIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}</style>
<script>
(function(){
var entries={$entries};
if(!entries.length)return;
var box=document.getElementById('cms-sp');
var idx=Math.floor(Math.random()*entries.length);
function show(){
  var e=entries[idx%entries.length];idx++;
  document.getElementById('cms-sp-name').textContent=e.name+(e.city?', '+e.city:'');
  document.getElementById('cms-sp-desc').textContent=e.product?'замовив: '+e.product:'щойно зробив замовлення';
  document.getElementById('cms-sp-time').textContent=e.time||'щойно';
  box.style.display='block';
  setTimeout(function(){box.style.display='none';setTimeout(show,{$interval});},{$duration});
}
setTimeout(show,{$delay});
})();
</script>
HTML;
        }

        $sectionsHtml = '';
        foreach ($landing['sections'] ?? [] as $section) {
            if (empty($section['visible']) && !$adminMode) continue;
            $sectionsHtml .= $this->renderSection($section, $adminMode);
        }

        $adminBar = '';
        if ($adminMode) {
            $adminBar = '<div id="cms-admin-bar" style="position:fixed;top:0;left:0;right:0;height:44px;background:#1e293b;z-index:99999;display:flex;align-items:center;padding:0 16px;gap:12px;">
                <span style="color:#fff;font-size:13px;font-weight:600;">Landiro CMS</span>
                <span style="color:#64748b;">|</span>
                <span id="cms-save-status" style="color:#94a3b8;font-size:12px;">Збережено</span>
            </div><div style="height:44px;"></div>';
        }

        $faviconTag = $favicon ? "<link rel=\"icon\" href=\"{$favicon}\">" : '';
        $ogTag = $ogImage ? "<meta property=\"og:image\" content=\"{$ogImage}\">" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
<meta name="description" content="{$desc}">
{$faviconTag}
{$ogTag}
<meta property="og:title" content="{$title}">
<meta property="og:description" content="{$desc}">
{$gtmHead}
{$gaScript}
{$fbPixel}
{$ttPixel}
{$snapPixel}
{$globalCssVars}
{$customCss}
{$headScripts}
<style>*{box-sizing:border-box;}body{margin:0;padding:0;}</style>
</head>
<body>
{$gtmBody}
{$adminBar}
{$countdownHtml}
{$sectionsHtml}
{$utmScript}
{$successUrlScript}
{$bodyEndScripts}
{$popupHtml}
{$stickyBar}
{$floatingWidget}
{$socialProofHtml}
</body>
</html>
HTML;
    }

    public function renderSection(array $section, bool $adminMode = false): string {
        $id = htmlspecialchars($section['id'] ?? '');
        $type = htmlspecialchars($section['type'] ?? 'custom');
        $html = $section['html'] ?? '';
        $css = $section['css'] ?? '';
        $js = $section['js'] ?? '';
        $vars = $section['data']['vars'] ?? [];

        // Load from template files when html is not pre-rendered
        if ($html === '' && !empty($section['template'])) {
            $tplDir = TEMPLATES_PATH . '/' . $type . '/' . $section['template'];
            $data   = $section['data'] ?? [];

            if (file_exists($tplDir . '/template.html')) {
                $tplHtml = file_get_contents($tplDir . '/template.html');
                foreach ($data as $k => $v) {
                    $tplHtml = str_replace('{{' . $k . '}}', htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $tplHtml);
                }
                // Clear any unreplaced placeholders
                $html = preg_replace('/\{\{[A-Z0-9_]+\}\}/', '', $tplHtml);
            }
            if ($css === '' && file_exists($tplDir . '/style.css')) {
                $css = file_get_contents($tplDir . '/style.css');
            }
            if ($js === '' && file_exists($tplDir . '/script.js')) {
                $js = file_get_contents($tplDir . '/script.js');
            }
        }

        $varsCss = '';
        if ($vars) {
            $varsCss .= "#section-{$id}{";
            foreach ($vars as $k => $v) {
                $varsCss .= htmlspecialchars($k) . ':' . htmlspecialchars($v) . ';';
            }
            $varsCss .= '}';
        }

        $styleBlock = ($css || $varsCss) ? "<style>{$varsCss}{$css}</style>" : '';
        $scriptBlock = $js ? "<script>{$js}</script>" : '';

        $adminAttrs = '';
        if ($adminMode) {
            $adminAttrs = ' data-section-id="' . $id . '" data-section-type="' . $type . '"';
        }

        $visible = ($section['visible'] ?? true) ? '' : ' style="opacity:0.4;pointer-events:none;"';

        return "\n{$styleBlock}\n<div id=\"section-{$id}\" class=\"cms-section cms-section-{$type}\"{$adminAttrs}{$visible}>\n{$html}\n</div>\n{$scriptBlock}\n";
    }
}
