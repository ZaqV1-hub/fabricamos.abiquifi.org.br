-- Fabricamos layout and hierarchy upgrade (v2)
-- Date: 2026-03-25
-- DB: fab.abc_teste

UPDATE oyqm_posts
SET post_content = '<div class="fab-shell fab-shell--comprador"><div class="fab-shell__hero"><h1 class="fab-shell__title">Fabricamos</h1><p class="fab-shell__subtitle">Catálogo de fabricantes do ecossistema Abiquifi para consulta, descoberta e navegação profissional por setor, empresa e substância.</p></div>[fabricamos_catalogo mode="comprador"]</div>'
WHERE ID = 35133;

UPDATE oyqm_posts
SET post_content = '<div class="fab-shell fab-shell--fabricante"><div class="fab-shell__hero"><h1 class="fab-shell__title">Fabricamos</h1><p class="fab-shell__subtitle">Ambiente de fabricantes com estrutura de catálogo e leitura clara para gestão, visibilidade e evolução do perfil empresarial.</p></div>[fabricamos_catalogo mode="fabricante"]</div>'
WHERE ID = 35134;

UPDATE oyqm_posts
SET post_content = CONCAT(
  post_content,
  '\n\n/* FABRICAMOS_LAYOUT_V2_START */\n',
  'body.fab-page-active #jupiterx-main{width:100%;max-width:100%;padding-left:0;padding-right:0;}\n',
  'body.fab-page-active .jupiterx-footer{display:block !important;}\n',
  '.fab-shell{max-width:min(1680px,calc(100vw - 64px));margin:clamp(20px,2.5vw,36px) auto clamp(36px,5vw,64px);padding:0;}\n',
  '.fab-shell__hero{display:grid;gap:10px;margin:0 0 clamp(18px,2vw,26px);padding:0 2px;}\n',
  '.fab-shell__title{margin:0;font-family:"Barlow","Montserrat","Poppins",Arial,sans-serif;font-size:clamp(30px,3vw,44px);font-weight:700;line-height:1.1;color:#132d60;letter-spacing:-0.02em;}\n',
  '.fab-shell__subtitle{margin:0;max-width:980px;font-family:"Montserrat","Poppins",Arial,sans-serif;font-size:clamp(15px,1.2vw,18px);line-height:1.55;color:#3d4b67;}\n',
  '.fab-shell .fab{max-width:none !important;margin:0 !important;padding:0 !important;background:transparent !important;border-radius:0 !important;}\n',
  '.fab-shell .fab-grid{display:grid;grid-template-columns:minmax(280px,29%) minmax(0,71%);gap:clamp(18px,2vw,28px);margin-top:0;align-items:start;}\n',
  '.fab-shell .fab-grid > .fab-box{background:#fff;border:1px solid #d9e3f1;border-radius:14px;padding:clamp(16px,1.2vw,22px);box-shadow:0 10px 28px rgba(19,45,96,.07);min-width:0;}\n',
  '.fab-shell .fab-grid > .fab-box:nth-child(1){grid-column:1;position:sticky;top:calc(var(--fab-header-h,120px) + 20px);}\n',
  '.fab-shell .fab-grid > .fab-box:nth-child(2){grid-column:2;}\n',
  '.fab-shell .fab-grid > .fab-box:nth-child(3){grid-column:2;}\n',
  '.fab-shell .fab-title{font-size:15px;font-weight:700;letter-spacing:.01em;margin:0 0 14px;color:#132d60;}\n',
  '.fab-shell .fab-title:after{height:2px;background:#2f6fd6;opacity:.95;}\n',
  '.fab-shell .fab-field{height:38px;padding:0 12px;border:1px solid #cfd9e8;border-radius:8px;font-size:13px;margin-bottom:10px;background:#f8fbff;color:#263653;}\n',
  '.fab-shell .fab-field:focus{outline:none;border-color:#2f6fd6;box-shadow:0 0 0 3px rgba(47,111,214,.14);}\n',
  '.fab-shell .fab-btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:10px 14px;font-size:13px;font-weight:600;border-radius:10px;background:#15346d;color:#fff;line-height:1;text-decoration:none;}\n',
  '.fab-shell .fab-btn:hover{background:#1b438c;color:#fff;}\n',
  '.fab-shell .fab-grid > .fab-box:nth-child(2) .fab-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;}\n',
  '@media (min-width:1450px){.fab-shell .fab-grid > .fab-box:nth-child(2) .fab-cards{grid-template-columns:repeat(4,minmax(0,1fr));}}\n',
  '.fab-shell .fab-card{min-height:132px;padding:16px;border:1px solid #d6dfec;border-radius:12px;background:#fff;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;}\n',
  '.fab-shell .fab-card:hover{transform:translateY(-2px);border-color:#bfd0eb;box-shadow:0 12px 22px rgba(19,45,96,.11);}\n',
  '.fab-shell .fab-card img{max-width:180px;max-height:64px;}\n',
  '.fab-shell .fab-list li{padding:10px 0;border-bottom:1px solid #e3e9f3;}\n',
  '.fab-shell .fab-list a{font-size:13px;font-weight:500;color:#244f99;text-decoration:none;}\n',
  '.fab-shell .fab-list a:hover{color:#163b78;text-decoration:underline;}\n',
  '.fab-shell .fab-grid > .fab-box:nth-child(3) p{margin:0 0 14px;font-size:13px;line-height:1.55;color:#3c4a65;}\n',
  '.fab-shell .fab-pg{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:16px;}\n',
  '.fab-shell .fab-pg a,.fab-shell .fab-pg span{min-width:28px;height:28px;line-height:28px;border-radius:999px;font-size:12px;background:#edf2fa;color:#15346d;font-weight:600;}\n',
  '.fab-shell .fab-pg .on{background:#15346d;color:#fff;}\n',
  '.fab-shell .fab-pg em{margin-left:auto;font-size:12px;color:#5a6882;}\n',
  '.fab-shell .fab-grid > .fab-box:nth-child(2) > p:only-of-type{margin:0;padding:16px 14px;border-radius:10px;background:#f4f8ff;border:1px dashed #c9d6ea;color:#35507f;font-size:13px;line-height:1.5;}\n',
  '@media (max-width:1240px){.fab-shell{max-width:calc(100vw - 40px);} .fab-shell .fab-grid{grid-template-columns:minmax(260px,32%) minmax(0,68%);} .fab-shell .fab-grid > .fab-box:nth-child(2) .fab-cards{grid-template-columns:repeat(2,minmax(0,1fr));}}\n',
  '@media (max-width:1024px){.fab-shell{max-width:calc(100vw - 28px);margin:18px auto 36px;} .fab-shell .fab-grid{grid-template-columns:1fr;gap:14px;} .fab-shell .fab-grid > .fab-box:nth-child(1),.fab-shell .fab-grid > .fab-box:nth-child(2),.fab-shell .fab-grid > .fab-box:nth-child(3){grid-column:1;position:relative;top:auto;} .fab-shell .fab-grid > .fab-box:nth-child(2) .fab-cards{grid-template-columns:repeat(2,minmax(0,1fr));}}\n',
  '@media (max-width:680px){.fab-shell .fab-grid > .fab-box:nth-child(2) .fab-cards{grid-template-columns:1fr;} .fab-shell .fab-box{padding:14px;} .fab-shell .fab-field{height:36px;font-size:13px;} .fab-shell__title{font-size:32px;} .fab-shell__subtitle{font-size:15px;}}\n',
  '/* FABRICAMOS_LAYOUT_V2_END */\n'
)
WHERE ID = 33124
  AND post_content NOT LIKE '%FABRICAMOS_LAYOUT_V2_START%';

-- Normalize escaped line breaks (\n) into real line breaks for CSS parsing safety
UPDATE oyqm_posts
SET post_content = REPLACE(post_content, CHAR(92,110), CHAR(10))
WHERE ID = 33124
  AND post_content LIKE '%FABRICAMOS_LAYOUT_V2_START%';

SELECT ID, post_title, LEFT(post_content, 280) AS content_head
FROM oyqm_posts
WHERE ID IN (35133,35134);

SELECT ID, (post_content LIKE '%FABRICAMOS_LAYOUT_V2_START%') AS has_v2_marker
FROM oyqm_posts
WHERE ID = 33124;
