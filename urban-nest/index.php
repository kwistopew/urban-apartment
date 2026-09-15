<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: tenant/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title>Urban Nest | Apartment Rental Management</title>
    <script>(function(){try{if(localStorage.getItem('urbanNestTheme')==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
    <link rel="stylesheet" href="css/style.css?v=20260914-intro3">
<style id="landing-critical-css">
/* Critical landing-page CSS: keeps the public intro styled even when an old browser cache has the main stylesheet. */
html,body{margin:0!important;padding:0!important}body.landing-page{background:#f6f8fc;color:#172033;overflow-x:hidden;font-family:Arial,sans-serif}body.landing-page *{box-sizing:border-box}.landing-page a{text-decoration:none;color:inherit}.landing-header{position:sticky;top:0;z-index:100;min-height:76px;padding:12px clamp(18px,5vw,72px);display:flex;align-items:center;gap:28px;background:rgba(255,255,255,.94);backdrop-filter:blur(18px);border-bottom:1px solid #e5eaf1;box-shadow:0 4px 20px rgba(20,32,55,.04)}.landing-brand{display:flex;align-items:center;gap:10px;margin-right:auto}.landing-brand-logo{width:43px;height:43px;padding:6px;border-radius:12px;background:#fff;box-shadow:0 7px 20px rgba(20,32,55,.1);display:grid;place-items:center}.landing-brand-logo img{width:100%;height:100%;object-fit:contain}.landing-brand strong,.landing-brand small{display:block}.landing-brand strong{font-size:16px;color:#0b1e5c}.landing-brand small{font-size:9px;color:#7887a0;margin-top:2px}.landing-nav{display:flex;align-items:center;gap:5px}.landing-nav a{position:relative;padding:10px 13px;color:#536177;font-size:12px;font-weight:700;border-radius:10px;transition:all .25s ease}.landing-nav a:hover{color:#1f3ed0;background:#f0f3ff;transform:translateY(-1px)}.landing-nav .landing-login-link{background:#3857ff;color:#fff;padding:11px 16px;box-shadow:0 8px 20px rgba(56,87,255,.22)}.landing-nav .landing-login-link:hover{background:#263fd6;color:#fff}.landing-actions{display:flex;align-items:center;gap:9px}.language-toggle{border:1px solid #dce3ed;background:#fff;color:#536177;border-radius:999px;padding:8px 11px;font-size:10px;font-weight:800;transition:.25s ease}.language-toggle:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(20,32,55,.08)}.language-toggle .lang-active{color:#3857ff}.landing-menu-toggle{display:none;width:40px;height:40px;border:1px solid #dce3ed;background:#fff;border-radius:11px;color:#172033;font-size:18px}.landing-section{width:min(1240px,calc(100% - 44px));margin:0 auto}.landing-hero{min-height:calc(100vh - 76px);display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:clamp(35px,6vw,90px);padding:70px 0 80px}.landing-hero-copy{max-width:650px}.landing-kicker,.section-eyebrow{font-size:10px;letter-spacing:.16em;font-weight:900;color:#4b62d7}.landing-hero h1{font-size:clamp(42px,5.2vw,70px);line-height:1.02;letter-spacing:-.05em;margin:15px 0 21px;color:#0b1e5c;font-weight:800}.landing-hero p{max-width:600px;color:#65738a;font-size:16px;line-height:1.75;margin:0}.landing-hero-buttons{display:flex;flex-wrap:wrap;gap:11px;margin-top:30px}.landing-primary-btn,.landing-secondary-btn{display:inline-flex;align-items:center;justify-content:center;gap:18px;border-radius:12px;padding:14px 20px;font-size:12px;font-weight:800;transition:transform .28s ease,box-shadow .28s ease,background .28s ease}.landing-primary-btn{background:#3857ff;color:#fff;box-shadow:0 12px 26px rgba(56,87,255,.23)}.landing-primary-btn:hover{transform:translateY(-3px);background:#263fd6}.landing-secondary-btn{border:1px solid #dce3ed;background:#fff;color:#31405a}.landing-secondary-btn:hover{transform:translateY(-3px);box-shadow:0 12px 25px rgba(20,32,55,.08)}.landing-trust-row{display:flex;gap:24px;margin-top:42px;padding-top:20px;border-top:1px solid #e3e8ef}.landing-trust-row div{display:flex;flex-direction:column;gap:3px}.landing-trust-row strong{font-size:10px;color:#3857ff}.landing-trust-row span{font-size:10px;color:#7887a0}.landing-hero-visual{position:relative;min-height:570px;display:grid;place-items:center}.hero-image-card{position:relative;width:min(570px,100%);height:500px;border-radius:28px;overflow:hidden;box-shadow:0 30px 75px rgba(20,32,55,.2);transform:rotate(1deg);transition:transform .5s ease}.hero-image-card:hover{transform:rotate(0) translateY(-5px)}.hero-image-card img{width:100%;height:100%;object-fit:cover;display:block}.hero-image-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(9,25,69,.05),rgba(9,25,69,.46))}.hero-floating-card{position:absolute;z-index:3;display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.95);padding:12px 15px;border:1px solid rgba(255,255,255,.8);border-radius:15px;box-shadow:0 15px 30px rgba(12,25,50,.18);animation:floatCard 4s ease-in-out infinite}.hero-floating-card strong,.hero-floating-card small{display:block}.hero-floating-card strong{font-size:11px;color:#172033}.hero-floating-card small{font-size:9px;color:#7887a0;margin-top:2px}.hero-floating-top{top:50px;left:-30px}.hero-floating-bottom{right:-22px;bottom:48px;animation-delay:-1.7s}.floating-icon,.floating-check{width:32px;height:32px;border-radius:10px;display:grid;place-items:center;background:#eef1ff;color:#3857ff;font-weight:900}.hero-orb{position:absolute;border-radius:50%;pointer-events:none}.hero-orb-one{width:180px;height:180px;background:rgba(120,244,175,.3);right:-30px;top:5px}.hero-orb-two{width:120px;height:120px;background:rgba(112,146,255,.25);left:-45px;bottom:15px}.landing-intro{text-align:center;padding:115px 10% 105px}.landing-intro h2,.section-heading h2,.how-copy h2,.landing-cta h2{color:#0b1e5c;letter-spacing:-.04em;line-height:1.08}.landing-intro h2{font-size:clamp(32px,4vw,52px);margin:13px auto 16px;max-width:800px}.landing-intro p{max-width:760px;margin:auto;color:#718096;line-height:1.8;font-size:14px}.landing-features{padding:40px 0 120px}.section-heading{display:flex;align-items:end;justify-content:space-between;gap:30px;margin-bottom:35px}.section-heading h2{font-size:clamp(30px,3.5vw,46px);margin:12px 0 0}.section-heading>p{max-width:370px;color:#718096;font-size:12px;line-height:1.7;margin:0}.feature-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:15px}.feature-card{position:relative;background:#fff;border:1px solid #e4e9f0;border-radius:20px;padding:25px;min-height:275px;overflow:hidden;transition:transform .3s ease,box-shadow .3s ease}.feature-card:hover{transform:translateY(-7px);box-shadow:0 20px 45px rgba(20,32,55,.1)}.feature-number{position:absolute;top:20px;right:20px;font-size:9px;font-weight:900;color:#a0adbf}.feature-icon{width:44px;height:44px;border-radius:13px;background:#eef1ff;color:#3857ff;display:grid;place-items:center;font-weight:900;font-size:17px;margin-bottom:55px}.feature-card h3{font-size:16px;margin:0 0 9px}.feature-card p{font-size:11px;line-height:1.7;color:#7887a0;margin:0}.landing-how{display:grid;grid-template-columns:1fr 1fr;gap:clamp(45px,7vw,100px);align-items:center;padding:80px 0 130px}.how-visual{position:relative}.how-visual img{width:100%;height:560px;object-fit:cover;border-radius:26px;box-shadow:0 25px 60px rgba(20,32,55,.16);display:block}.how-badge{position:absolute;right:-20px;bottom:28px;background:#0f172a;color:#fff;border-radius:15px;padding:14px 17px;box-shadow:0 12px 30px rgba(15,23,42,.25)}.how-badge strong,.how-badge span{display:block}.how-badge strong{font-size:12px}.how-badge span{font-size:9px;color:#aebbd0;margin-top:3px}.how-copy h2{font-size:clamp(32px,4vw,50px);margin:12px 0 30px}.how-step{display:flex;gap:16px;padding:17px 0;border-bottom:1px solid #e5eaf1}.how-step>span{width:31px;height:31px;flex:0 0 31px;border-radius:50%;display:grid;place-items:center;background:#eef1ff;color:#3857ff;font-weight:900;font-size:10px}.how-step h3{font-size:13px;margin:0 0 4px}.how-step p{font-size:11px;color:#7887a0;line-height:1.6;margin:0}.how-copy .landing-primary-btn{margin-top:27px}.landing-cta{margin-bottom:90px;padding:45px 50px;border-radius:26px;background:linear-gradient(135deg,#0d1c4d,#263fd6);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:30px;box-shadow:0 25px 55px rgba(25,48,150,.2)}.landing-cta .section-eyebrow{color:#aebcff}.landing-cta h2{color:#fff;font-size:clamp(27px,3.4vw,43px);margin:10px 0 8px}.landing-cta p{font-size:11px;color:#cbd5e4;margin:0;max-width:580px}.landing-cta .landing-primary-btn{background:#fff;color:#263fd6;box-shadow:none;white-space:nowrap}.landing-team{padding:20px 0 105px}.team-heading{text-align:center;max-width:760px;margin:0 auto 38px}.team-heading h2{font-size:clamp(32px,4vw,50px);color:#0b1e5c;letter-spacing:-.04em;line-height:1.08;margin:12px 0 12px}.team-heading p{max-width:680px;margin:auto;color:#718096;font-size:13px;line-height:1.75}.team-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}.team-card{background:#fff;border:1px solid #e4e9f0;border-radius:20px;overflow:hidden;box-shadow:0 10px 28px rgba(20,32,55,.05);transition:transform .35s ease,box-shadow .35s ease,border-color .35s ease}.team-card:hover{transform:translateY(-8px);box-shadow:0 22px 48px rgba(20,32,55,.12);border-color:#d4dcf0}.team-photo{height:260px;background:linear-gradient(135deg,#edf1f7,#f8fafc);position:relative;overflow:hidden}.team-photo:after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(56,87,255,.04),transparent 55%)}.team-info{padding:17px 18px 20px}.team-info h3{font-size:14px;line-height:1.35;color:#18263e;margin:0 0 6px}.team-info span{font-size:10px;font-weight:800;color:#3857ff}.team-card:nth-child(2) .team-photo,.team-card:nth-child(4) .team-photo{background:linear-gradient(135deg,#f1f4f8,#e9edf4)}.team-card:nth-child(3) .team-photo{background:linear-gradient(135deg,#eef2ff,#f8faff)}
.landing-footer{padding:0 22px 28px;display:flex;justify-content:space-between;gap:15px;max-width:1240px;margin:auto;color:#8995a7;font-size:9px}.reveal-on-scroll{opacity:0;transform:translateY(35px);transition:opacity .75s ease,transform .8s cubic-bezier(.22,.8,.24,1)}.reveal-on-scroll.revealed{opacity:1;transform:none}@keyframes floatCard{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
@media(max-width:1050px){.landing-header{padding-left:25px;padding-right:25px}.landing-hero{gap:35px}.hero-floating-top{left:-10px}.hero-floating-bottom{right:-10px}.feature-cards{grid-template-columns:repeat(2,1fr)}}
@media(max-width:1050px){.team-grid{grid-template-columns:repeat(3,1fr)}.team-photo{height:230px}}
@media(max-width:820px){.landing-header{min-height:68px}.landing-nav{position:absolute;top:calc(100% + 8px);left:18px;right:18px;display:flex;flex-direction:column;align-items:stretch;padding:9px;background:#fff;border:1px solid #e4e9f0;border-radius:16px;box-shadow:0 18px 45px rgba(20,32,55,.13);opacity:0;visibility:hidden;transform:translateY(-8px);transition:.25s ease}.landing-nav.open{opacity:1;visibility:visible;transform:none}.landing-nav a{padding:12px 13px}.landing-nav .landing-login-link{text-align:center}.landing-menu-toggle{display:grid;place-items:center}.landing-hero{grid-template-columns:1fr;padding-top:55px}.landing-hero-copy{max-width:720px}.landing-hero-visual{min-height:470px}.hero-image-card{height:440px}.landing-intro{padding-top:90px}.landing-how{grid-template-columns:1fr;padding-top:40px}.how-visual img{height:440px}.landing-cta{align-items:flex-start;flex-direction:column}.landing-footer{flex-direction:column}}
@media(max-width:700px){.team-grid{grid-template-columns:repeat(2,1fr);gap:12px}.team-photo{height:210px}.team-info{padding:15px}.team-info h3{font-size:13px}}
@media(max-width:560px){.landing-section{width:calc(100% - 28px)}.landing-header{padding:10px 14px}.landing-brand-logo{width:38px;height:38px}.landing-brand strong{font-size:13px}.landing-brand small{font-size:8px}.landing-actions{gap:5px}.language-toggle{padding:7px 9px}.landing-hero{min-height:auto;padding-top:50px;padding-bottom:70px}.landing-hero h1{font-size:38px}.landing-hero p{font-size:13px}.landing-hero-buttons{display:grid;grid-template-columns:1fr}.landing-primary-btn,.landing-secondary-btn{width:100%}.landing-trust-row{display:grid;grid-template-columns:1fr;gap:12px;margin-top:30px}.landing-hero-visual{min-height:360px}.hero-image-card{height:350px;border-radius:21px}.hero-floating-top{top:18px;left:8px}.hero-floating-bottom{right:8px;bottom:20px}.landing-intro{padding:75px 10%}.landing-intro h2{font-size:30px}.section-heading{display:block}.section-heading>p{margin-top:15px}.landing-features{padding-bottom:80px}.feature-cards{grid-template-columns:1fr}.feature-card{min-height:230px}.feature-icon{margin-bottom:35px}.landing-how{padding-bottom:85px}.how-visual img{height:350px}.how-badge{right:10px}.landing-cta{padding:32px 25px;margin-bottom:60px}.landing-team{padding-bottom:70px}.team-grid{grid-template-columns:1fr}.team-photo{height:220px}.landing-footer{padding-bottom:22px}}
.landing-page.auth-leaving-right .landing-hero-copy{animation:landingCopyLeave .32s ease both}.landing-page.auth-leaving-right .landing-hero-visual{animation:landingVisualLeave .38s ease both}@keyframes landingCopyLeave{to{opacity:0;transform:translateX(-28px)}}@keyframes landingVisualLeave{to{opacity:0;transform:translateX(28px) scale(.98)}}@media(prefers-reduced-motion:reduce){.landing-page.auth-leaving-right .landing-hero-copy,.landing-page.auth-leaving-right .landing-hero-visual,.reveal-on-scroll,.hero-floating-card,.landing-nav a,.landing-primary-btn,.landing-secondary-btn,.feature-card{transition:none!important;animation:none!important}.reveal-on-scroll{opacity:1;transform:none}}
/* Guaranteed public-introduction dark mode */
html[data-theme="dark"] body.landing-page,
html[data-theme="dark"] body.landing-page main,
html[data-theme="dark"] body.landing-page .landing-section {
  background-color:#0b111b !important;
  color:#e8edf7 !important;
}
html[data-theme="dark"] body.landing-page .landing-header {
  background:rgba(11,17,27,.94) !important;
  border-bottom-color:#273244 !important;
  box-shadow:0 4px 24px rgba(0,0,0,.28) !important;
}
html[data-theme="dark"] body.landing-page .landing-brand strong,
html[data-theme="dark"] body.landing-page .landing-hero h1,
html[data-theme="dark"] body.landing-page .landing-intro h2,
html[data-theme="dark"] body.landing-page .section-heading h2,
html[data-theme="dark"] body.landing-page .how-copy h2,
html[data-theme="dark"] body.landing-page .team-heading h2 {
  color:#f4f7ff !important;
}
html[data-theme="dark"] body.landing-page .landing-brand small,
html[data-theme="dark"] body.landing-page .landing-hero p,
html[data-theme="dark"] body.landing-page .landing-intro p,
html[data-theme="dark"] body.landing-page .section-heading>p,
html[data-theme="dark"] body.landing-page .team-heading p,
html[data-theme="dark"] body.landing-page .feature-content p,
html[data-theme="dark"] body.landing-page .how-step p,
html[data-theme="dark"] body.landing-page .landing-trust-row span {
  color:#aeb9cb !important;
}
html[data-theme="dark"] body.landing-page .landing-nav a { color:#c4cede !important; }
html[data-theme="dark"] body.landing-page .landing-nav a:hover { color:#fff !important; background:#18243a !important; }
html[data-theme="dark"] body.landing-page .language-toggle,
html[data-theme="dark"] body.landing-page .landing-theme-toggle,
html[data-theme="dark"] body.landing-page .landing-menu-toggle {
  background:#151e2c !important;
  border-color:#334155 !important;
  color:#e7edf8 !important;
}
html[data-theme="dark"] body.landing-page .language-toggle .lang-active { color:#8ea4ff !important; }
html[data-theme="dark"] body.landing-page .landing-secondary-btn {
  background:#151e2c !important;
  border-color:#334155 !important;
  color:#e7edf8 !important;
}
html[data-theme="dark"] body.landing-page .landing-secondary-btn:hover { background:#1d2939 !important; }
html[data-theme="dark"] body.landing-page .landing-trust-row,
html[data-theme="dark"] body.landing-page .how-step { border-color:#273244 !important; }
html[data-theme="dark"] body.landing-page .feature-card,
html[data-theme="dark"] body.landing-page .team-card {
  background:#141c28 !important;
  border-color:#2d3a4d !important;
  box-shadow:0 12px 32px rgba(0,0,0,.22) !important;
}
html[data-theme="dark"] body.landing-page .feature-card:hover,
html[data-theme="dark"] body.landing-page .team-card:hover { border-color:#4a5c78 !important; }
html[data-theme="dark"] body.landing-page .feature-content h3,
html[data-theme="dark"] body.landing-page .team-info h3,
html[data-theme="dark"] body.landing-page .how-step h3 { color:#f1f5fb !important; }
html[data-theme="dark"] body.landing-page .feature-image-wrap,
html[data-theme="dark"] body.landing-page .team-photo {
  background:linear-gradient(135deg,#1a2433,#111925) !important;
}
html[data-theme="dark"] body.landing-page .feature-card-image .feature-number {
  background:rgba(21,29,42,.92) !important;
  color:#cbd5e1 !important;
}
html[data-theme="dark"] body.landing-page .feature-icon,
html[data-theme="dark"] body.landing-page .how-step>span {
  background:#1b2a49 !important;
  color:#9db0ff !important;
}
html[data-theme="dark"] body.landing-page .hero-floating-card {
  background:rgba(20,28,40,.96) !important;
  border-color:#334155 !important;
}
html[data-theme="dark"] body.landing-page .hero-floating-card strong { color:#f1f5fb !important; }
html[data-theme="dark"] body.landing-page .hero-floating-card small { color:#aeb9cb !important; }
html[data-theme="dark"] body.landing-page .landing-footer {
  background:#080d15 !important;
  color:#aeb9cb !important;
  border-top-color:#273244 !important;
}
/* Team text is centered and easier to read */
body.landing-page .team-info { text-align:center !important; }
body.landing-page .team-info h3 { font-size:16px !important; margin-bottom:9px !important; }
body.landing-page .team-info .team-role {
  display:block !important;
  font-size:12px !important;
  line-height:1.35 !important;
  font-weight:900 !important;
  letter-spacing:.02em !important;
  margin-bottom:10px !important;
  color:#3857ff !important;
}
body.landing-page .team-info p {
  font-size:11px !important;
  line-height:1.7 !important;
  margin:0 auto !important;
  max-width:235px !important;
  text-align:center !important;
}
html[data-theme="dark"] body.landing-page .team-info .team-role { color:#9db0ff !important; }
@media(max-width:700px){
  body.landing-page .team-info h3 { font-size:14px !important; }
  body.landing-page .team-info .team-role { font-size:11px !important; }
  body.landing-page .team-info p { font-size:10px !important; max-width:220px !important; }
}
/* Restored smooth public-intro motion */
.landing-page .reveal-on-scroll{opacity:0;transform:translate3d(0,42px,0) scale(.985);filter:blur(2px);transition:opacity .8s cubic-bezier(.22,.61,.36,1),transform .9s cubic-bezier(.22,.61,.36,1),filter .8s ease;transition-delay:var(--reveal-delay,0ms);will-change:opacity,transform,filter}
.landing-page .reveal-on-scroll.revealed{opacity:1;transform:translate3d(0,0,0) scale(1);filter:blur(0)}
.landing-page .landing-header{transition:background .35s ease,box-shadow .35s ease,border-color .35s ease,transform .35s ease}
.landing-page .landing-header.scrolled{box-shadow:0 10px 30px rgba(20,32,55,.12)}
.landing-page .landing-hero-copy.reveal-on-scroll{transform:translate3d(-35px,24px,0);}
.landing-page .landing-hero-visual.reveal-on-scroll{transform:translate3d(35px,24px,0) scale(.985);}
.landing-page .landing-hero-copy.reveal-on-scroll.revealed,.landing-page .landing-hero-visual.reveal-on-scroll.revealed{transform:translate3d(0,0,0) scale(1)}
.landing-page .feature-card,.landing-page .team-card{transition:transform .38s cubic-bezier(.22,.61,.36,1),box-shadow .38s ease,border-color .3s ease,background .35s ease,color .35s ease}
.landing-page .landing-nav a,.landing-page .landing-primary-btn,.landing-page .landing-secondary-btn,.landing-page .language-toggle,.landing-page .landing-theme-toggle{transition:transform .25s ease,background .3s ease,color .3s ease,box-shadow .3s ease,border-color .3s ease}
@keyframes landingHeroTextIn{from{opacity:0;transform:translate3d(-30px,18px,0)}to{opacity:1;transform:none}}
@keyframes landingHeroVisualIn{from{opacity:0;transform:translate3d(30px,12px,0) scale(.97)}to{opacity:1;transform:none}}
.landing-page .landing-kicker{animation:landingHeroTextIn .65s ease both .08s}
.landing-page .landing-hero h1{animation:landingHeroTextIn .8s cubic-bezier(.22,.61,.36,1) both .16s}
.landing-page .landing-hero-copy>p{animation:landingHeroTextIn .7s ease both .28s}
.landing-page .landing-hero-buttons{animation:landingHeroTextIn .7s ease both .4s}
.landing-page .hero-image-card{animation:landingHeroVisualIn .9s cubic-bezier(.22,.61,.36,1) both .15s}
@media(prefers-reduced-motion:reduce){.landing-page .reveal-on-scroll,.landing-page .landing-hero-copy.reveal-on-scroll,.landing-page .landing-hero-visual.reveal-on-scroll,.landing-page .landing-kicker,.landing-page .landing-hero h1,.landing-page .landing-hero-copy>p,.landing-page .landing-hero-buttons,.landing-page .hero-image-card{opacity:1;transform:none;filter:none;animation:none!important;transition:none!important}}

</style>
</head>
<body class="landing-page">
<header class="landing-header" id="landingHeader">
    <a class="landing-brand" href="index.php" aria-label="Urban Nest home">
        <span class="landing-brand-logo"><img src="assets/urban-nest-logo.png" alt="Urban Nest logo"></span>
        <span><strong>Urban Nest</strong><small>Apartment Rental Management</small></span>
    </a>
    <nav class="landing-nav" id="landingNav" aria-label="Main navigation">
        <a href="#home" data-i18n="nav_home">Home</a>
        <a href="#features" data-i18n="nav_features">Features</a>
        <a href="#how-it-works" data-i18n="nav_how">How It Works</a>
        <a class="landing-login-link" href="login.php" data-auth-transition="right" data-i18n="nav_login">Log In <span>→</span></a>
    </nav>
    <div class="landing-actions">
        <button class="language-toggle" id="languageToggle" type="button" aria-label="Change language"><span class="lang-active">EN</span><span> / </span><span>TL</span></button>
        <button class="theme-toggle landing-theme-toggle" id="themeToggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode"><span class="theme-icon">☾</span><span class="theme-label">Dark mode</span></button>
        <button class="landing-menu-toggle" id="landingMenuToggle" type="button" aria-label="Open navigation" aria-expanded="false">☰</button>
    </div>
</header>

<main>
<section class="landing-hero landing-section" id="home">
    <div class="landing-hero-copy reveal-on-scroll">
        <span class="landing-kicker" data-i18n="hero_kicker">APARTMENT RENTAL MANAGEMENT</span>
        <h1 data-i18n="hero_title">A simpler way to find, manage, and enjoy your next home.</h1>
        <p data-i18n="hero_text">Urban Nest brings apartments, rental requests, tenants, payments, and support together in one organized workspace.</p>
        <div class="landing-hero-buttons">
            <a class="landing-primary-btn landing-login-link" href="login.php" data-auth-transition="right"><span data-i18n="hero_cta">Get Started</span><span>→</span></a>
            <a class="landing-secondary-btn" href="#features" data-i18n="hero_secondary">Explore Features</a>
        </div>
        <div class="landing-trust-row">
            <div><strong>01</strong><span data-i18n="trust_one">Easy apartment browsing</span></div>
            <div><strong>02</strong><span data-i18n="trust_two">Organized rental requests</span></div>
            <div><strong>03</strong><span data-i18n="trust_three">Simple payment tracking</span></div>
        </div>
    </div>
    <div class="landing-hero-visual reveal-on-scroll">
        <div class="hero-image-card">
            <img src="assets/auth-backgrounds/apartment-1.png" alt="Modern apartment interior">
            <div class="hero-image-overlay"></div>
            <div class="hero-floating-card hero-floating-top"><span class="floating-icon">⌂</span><span><strong data-i18n="float_home">Your next home</strong><small data-i18n="float_ready">Ready to explore</small></span></div>
            <div class="hero-floating-card hero-floating-bottom"><span class="floating-check">✓</span><span><strong data-i18n="float_rentals">Rental management</strong><small data-i18n="float_all">Everything in one place</small></span></div>
        </div>
        <div class="hero-orb hero-orb-one"></div><div class="hero-orb hero-orb-two"></div>
    </div>
</section>

<section class="landing-section landing-intro reveal-on-scroll">
    <div class="section-eyebrow" data-i18n="intro_kicker">BUILT FOR A BETTER RENTAL EXPERIENCE</div>
    <h2 data-i18n="intro_title">Less searching. Less paperwork. More confidence.</h2>
    <p data-i18n="intro_text">Whether you are looking for an available apartment or managing a property, Urban Nest keeps the important details easy to see and easy to manage.</p>
</section>

<section class="landing-section landing-features" id="features">
    <div class="section-heading reveal-on-scroll"><div><span class="section-eyebrow" data-i18n="features_kicker">FEATURES</span><h2 data-i18n="features_title">Everything you need in one place.</h2></div><p data-i18n="features_text">A clean workspace designed to make everyday rental tasks easier.</p></div>
    <div class="feature-cards">
        <article class="feature-card feature-card-image reveal-on-scroll"><span class="feature-number">01</span><div class="feature-image-wrap"><img src="assets/features/apartment-browse.png" alt="Modern apartment interior"></div><div class="feature-content"><h3 data-i18n="feature_1_title">Browse Apartments</h3><p data-i18n="feature_1_text">View available units, rental details, and apartment information before making a request.</p></div></article>
        <article class="feature-card feature-card-image reveal-on-scroll"><span class="feature-number">02</span><div class="feature-image-wrap illustration"><img src="assets/features/request-management.png" alt="Rental request management illustration"></div><div class="feature-content"><h3 data-i18n="feature_2_title">Manage Requests</h3><p data-i18n="feature_2_text">Keep rental requests organized from submission through approval.</p></div></article>
        <article class="feature-card feature-card-image reveal-on-scroll"><span class="feature-number">03</span><div class="feature-image-wrap illustration"><img src="assets/features/payment-tracking.png" alt="Payment tracking illustration"></div><div class="feature-content"><h3 data-i18n="feature_3_title">Track Payments</h3><p data-i18n="feature_3_text">See payment records and rental status without digging through paperwork.</p></div></article>
        <article class="feature-card feature-card-image reveal-on-scroll"><span class="feature-number">04</span><div class="feature-image-wrap illustration connected"><img src="assets/features/stay-connected.png" alt="Stay connected illustration"></div><div class="feature-content"><h3 data-i18n="feature_4_title">Stay Connected</h3><p data-i18n="feature_4_text">Use customer support messages to communicate with the rental team.</p></div></article>
    </div>
</section>

<section class="landing-section landing-how" id="how-it-works">
    <div class="how-visual reveal-on-scroll"><img src="assets/auth-backgrounds/apartment-3.png" alt="Apartment living space"><div class="how-badge"><strong>Urban Nest</strong><span data-i18n="how_badge">Simple by design</span></div></div>
    <div class="how-copy reveal-on-scroll"><span class="section-eyebrow" data-i18n="how_kicker">HOW IT WORKS</span><h2 data-i18n="how_title">Start with a few simple steps.</h2>
        <div class="how-step"><span>1</span><div><h3 data-i18n="step1_title">Create an account</h3><p data-i18n="step1_text">Register as a tenant and sign in securely.</p></div></div>
        <div class="how-step"><span>2</span><div><h3 data-i18n="step2_title">Explore available apartments</h3><p data-i18n="step2_text">Compare units and choose a place that fits your needs.</p></div></div>
        <div class="how-step"><span>3</span><div><h3 data-i18n="step3_title">Manage your rental</h3><p data-i18n="step3_text">Follow requests, payments, messages, and rental updates from your dashboard.</p></div></div>
        <a class="landing-primary-btn landing-login-link" href="login.php" data-auth-transition="right"><span data-i18n="how_cta">Log In to Urban Nest</span><span>→</span></a>
    </div>
</section>

<section class="landing-cta landing-section reveal-on-scroll">
    <div><span class="section-eyebrow" data-i18n="cta_kicker">READY WHEN YOU ARE</span><h2 data-i18n="cta_title">Make apartment rental management feel simple.</h2><p data-i18n="cta_text">Enter Urban Nest and keep your rental journey organized from one place.</p></div>
    <a class="landing-primary-btn" href="login.php" data-auth-transition="right"><span data-i18n="cta_button">Continue to Login</span><span>→</span></a>
</section>
<section class="landing-section landing-team" id="team">
    <div class="team-heading reveal-on-scroll">
        <span class="section-eyebrow" data-i18n="team_kicker">OUR TEAM</span>
        <h2 data-i18n="team_title">Meet the Team</h2>
        <p data-i18n="team_text">The people behind Urban Nest, working together to create a simple rental management experience.</p>
    </div>
    <div class="team-grid">
        <article class="team-card reveal-on-scroll"><div class="team-photo" aria-label="Photo placeholder"></div><div class="team-info"><h3>Mathew Publico</h3><span class="team-role" data-i18n="team_role_1">UI Specialist</span><p data-i18n="team_desc_1">Designs intuitive interfaces and user experiences.</p></div></article>
        <article class="team-card reveal-on-scroll"><div class="team-photo" aria-label="Photo placeholder"></div><div class="team-info"><h3>Aldren Nabaja</h3><span class="team-role" data-i18n="team_role_2">Frontend Developer</span><p data-i18n="team_desc_2">Designs and implements the user interface, creating an intuitive and visually appealing experience.</p></div></article>
        <article class="team-card reveal-on-scroll"><div class="team-photo" aria-label="Photo placeholder"></div><div class="team-info"><h3>Lancz Cristofer Arceo</h3><span class="team-role" data-i18n="team_role_3">Lead Developer</span><p data-i18n="team_desc_3">Develops and maintains the research platform, ensuring a seamless user experience.</p></div></article>
        <article class="team-card reveal-on-scroll"><div class="team-photo" aria-label="Photo placeholder"></div><div class="team-info"><h3>Denver Tungcol</h3><span class="team-role" data-i18n="team_role_4">Security Tester</span><p data-i18n="team_desc_4">Tests the website's security measures, identifying vulnerabilities and ensuring data protection.</p></div></article>
        <article class="team-card reveal-on-scroll"><div class="team-photo" aria-label="Photo placeholder"></div><div class="team-info"><h3>Jullienne David</h3><span class="team-role" data-i18n="team_role_5">Frontend Tester</span><p data-i18n="team_desc_5">Tests and evaluates the website to ensure functionality and a smooth user experience.</p></div></article>
    </div>
</section>

</main>
<footer class="landing-footer"><span>© <?= date('Y') ?> Urban Nest</span><span data-i18n="footer_text">Apartment Rental Management System • School Project</span></footer>


<script src="js/script.js?v=20260914-intro3"></script>
</body>
</html>
