<?php
if (!defined('ABSPATH')) { exit; }

function fab_data() {
	return array(
		array('slug'=>'endogen','nome'=>'Endogen','setor'=>'Biotecnologia','associado'=>'Sim','origem'=>'Brasil','insumo'=>'Dipirona Sodica','dcb'=>'Dipirona Sodica','inn'=>'Metamizole Sodium','cas'=>'68-89-3','ncm'=>'3003.90.99','certificado'=>'CBPF','contato'=>'Pessoa1','telefone'=>'11 99999-9999','email'=>'empresa@company.com','site'=>'www.empresa.com','logo'=>home_url('/wp-content/uploads/2026/01/Logo-Endogen-Final-01-768x264.png'),'descricao'=>'A Endogen e uma empresa especializada no desenvolvimento e fornecimento de insumos farmaceuticos.','subs'=>array('Dipirona Sodica','Paracetamol','Amoxicilina Tri-hidratada','Cefalexina Monoidratada')),
		array('slug'=>'soroquality','nome'=>'Soroquality','setor'=>'Biotecnologia','associado'=>'Sim','origem'=>'Brasil','insumo'=>'Paracetamol','dcb'=>'Paracetamol','inn'=>'Paracetamol','cas'=>'103-90-2','ncm'=>'2924.29.45','certificado'=>'CBPF','contato'=>'Pessoa2','telefone'=>'11 98888-9999','email'=>'contato@soroquality.com','site'=>'www.soroquality.com','logo'=>home_url('/wp-content/uploads/2024/11/logo-soroquality-png-300x145.png'),'descricao'=>'Fabricante de insumos com foco em qualidade e rastreabilidade.','subs'=>array('Paracetamol','Dipirona Sodica','Ibuprofeno')),
		array('slug'=>'solabia','nome'=>'Solabia','setor'=>'Quimico','associado'=>'Sim','origem'=>'Internacional','insumo'=>'Amoxicilina Tri-hidratada','dcb'=>'Amoxicilina','inn'=>'Amoxicillin','cas'=>'61336-70-7','ncm'=>'2941.10.49','certificado'=>'ISO','contato'=>'Pessoa3','telefone'=>'11 97777-8888','email'=>'contato@solabia.com','site'=>'www.solabia.com','logo'=>home_url('/wp-content/uploads/2021/08/solabia.png'),'descricao'=>'Portfolio de especialidades para aplicacoes farmaceuticas.','subs'=>array('Amoxicilina Tri-hidratada','Cefalexina Monoidratada')),
		array('slug'=>'river-city','nome'=>'River City','setor'=>'Distribuicao','associado'=>'Nao','origem'=>'Brasil','insumo'=>'Cefalexina Monoidratada','dcb'=>'Cefalexina','inn'=>'Cephalexin','cas'=>'15686-71-2','ncm'=>'2941.90.39','certificado'=>'N/A','contato'=>'Pessoa4','telefone'=>'11 96666-7777','email'=>'contato@rivercity.com.br','site'=>'www.rivercity.com.br','logo'=>home_url('/wp-content/uploads/2021/08/river.png'),'descricao'=>'Fornecedor com atuacao nacional em insumos e materias-primas.','subs'=>array('Cefalexina Monoidratada','Amoxicilina Tri-hidratada')),
		array('slug'=>'promediol','nome'=>'Promediol','setor'=>'Distribuicao','associado'=>'Nao','origem'=>'Brasil','insumo'=>'Dipirona Sodica','dcb'=>'Dipirona Sodica','inn'=>'Metamizole Sodium','cas'=>'68-89-3','ncm'=>'3003.90.99','certificado'=>'N/A','contato'=>'Pessoa5','telefone'=>'11 95555-6666','email'=>'contato@promediol.com.br','site'=>'www.promediol.com.br','logo'=>home_url('/wp-content/uploads/2025/04/logo-promediol-colorido-300x74.png'),'descricao'=>'Empresa comercial focada em insumos para fabricantes.','subs'=>array('Dipirona Sodica','Paracetamol')),
		array('slug'=>'licks','nome'=>'Licks Attorneys','setor'=>'Servicos','associado'=>'Nao','origem'=>'Brasil','insumo'=>'Assessoria regulatoria','dcb'=>'-','inn'=>'-','cas'=>'-','ncm'=>'-','certificado'=>'-','contato'=>'Pessoa6','telefone'=>'11 94444-5555','email'=>'contato@licks.com.br','site'=>'www.licks.com.br','logo'=>home_url('/wp-content/uploads/2024/10/Logo-Licks-Attorneys-300x113.jpg'),'descricao'=>'Suporte juridico e regulatorio para o ecossistema farmaceutico.','subs'=>array('Suporte regulatorio','Propriedade intelectual'))
	);
}

function fab_find($slug) {
	foreach (fab_data() as $i) { if ($i['slug'] === $slug) { return $i; } }
	return fab_data()[0];
}

function fab_top() {
	return '';
}

function fab_assets() {
	if (!is_singular()) { return; }
	global $post;
	if (!$post) { return; }
	$tags = array('fabricamos_catalogo','fabricamos_detalhes','fabricamos_meu_fabricante','fabricamos_painel','fabricamos_login');
	$ok = false; foreach ($tags as $t) { if (has_shortcode($post->post_content, $t)) { $ok = true; break; } }
	if (!$ok) { return; }
	wp_register_style('fab-inline', false, array(), '1.0'); wp_enqueue_style('fab-inline');
	wp_add_inline_style('fab-inline', '.fab{max-width:1140px;margin:30px auto;background:#f1f3f5;padding:12px;border-radius:4px;font-family:"Poppins","Segoe UI",Arial,sans-serif}.fab-grid{display:grid;grid-template-columns:260px 1fr 330px;gap:14px;margin-top:10px}.fab-box{background:#fff;border:1px solid #dde2ea;border-radius:6px;padding:10px}.fab-title{font-size:13px;font-weight:700;color:#132d60;margin:0 0 8px;display:flex;align-items:center;gap:6px}.fab-title:after{content:"";height:2px;background:#2f6fd6;flex:1}.fab-field{width:100%;height:30px;border:1px solid #d5dbe5;border-radius:4px;padding:0 8px;font-size:11px;margin-bottom:6px;background:#f9fafc}.fab-btn{display:block;background:#132d60;color:#fff;text-align:center;border-radius:4px;padding:7px;font-size:11px;text-decoration:none;border:none;cursor:pointer}.fab-btn:hover{background:#1b3f80;color:#fff}.fab-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.fab-card{background:#fff;border:1px solid #dde2ea;border-radius:8px;min-height:92px;display:flex;align-items:center;justify-content:center;padding:10px}.fab-card img{max-width:140px;max-height:48px}.fab-list{list-style:none;margin:0;padding:0}.fab-list li{border-bottom:1px solid #e7ebf0;padding:7px 0}.fab-list li:last-child{border-bottom:0}.fab-list a{font-size:12px;color:#2d5fb7;text-decoration:none}.fab-pg{display:flex;gap:4px;margin-top:8px;align-items:center}.fab-pg a,.fab-pg span{font-size:10px;min-width:18px;height:18px;line-height:18px;text-align:center;background:#e7ebf0;border-radius:2px;text-decoration:none;color:#132d60}.fab-pg .on{background:#132d60;color:#fff}.fab-pg em{margin-left:auto;font-size:10px;color:#697489;font-style:normal}.fab-detailhead{display:flex;gap:8px;align-items:center}.fab-back{display:inline-flex;width:20px;height:20px;border-radius:50%;background:#132d60;color:#fff;align-items:center;justify-content:center;font-size:10px;text-decoration:none}.fab-h{font-size:14px;color:#132d60;font-weight:700;display:flex;gap:8px;align-items:center;flex:1}.fab-h:after{content:"";height:2px;background:#2f6fd6;flex:1}.fab-logo{background:#fff;border:1px solid #dde2ea;border-radius:8px;min-height:170px;display:flex;align-items:center;justify-content:center;margin:8px 0;padding:20px}.fab-logo img{max-width:300px;max-height:100px}.fab-text{background:#fff;border:1px solid #dde2ea;border-radius:6px;padding:10px;font-size:12px;line-height:1.5;color:#3c4861}.fab-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}.fab-label{font-size:10px;color:#4e5a73;display:block;margin-bottom:3px}.fab-contact,.fab-tablewrap{background:#fff;border:1px solid #dde2ea;border-radius:6px;padding:10px}.fab-subs{list-style:none;margin:8px 0 0;padding:0;background:#fff;border:1px solid #dde2ea;border-radius:6px}.fab-subs li{font-size:10px;padding:5px 8px;border-bottom:1px solid #e8edf2;display:flex;justify-content:space-between}.fab-subs li:last-child{border-bottom:0}.fab-login{max-width:740px;margin:30px auto;background:#f1f3f5;padding:12px;border-radius:6px;font-family:"Poppins","Segoe UI",Arial,sans-serif}.fab-login h2{margin:0 0 14px;font-size:31px}.fab-login #loginform p{margin:0 0 8px}.fab-login #loginform input[type=text],.fab-login #loginform input[type=password]{width:100%;height:30px;border:1px solid #d5dbe5;border-radius:4px;padding:0 8px;background:#f9fafc}.fab-login #wp-submit{background:#132d60;color:#fff;border:none;border-radius:4px;padding:6px 14px;font-size:11px}.fab-table{width:100%;min-width:960px;border-collapse:collapse}.fab-table th{background:#132d60;color:#fff;font-size:10px;padding:6px;text-align:left}.fab-table td{font-size:10px;padding:7px 6px;border-bottom:1px solid #ecf0f4}.fab-acts{display:flex;gap:6px;align-items:center}.fab-dot{width:7px;height:7px;border-radius:50%;display:inline-block}.fab-g{background:#2ea44f}.fab-r{background:#d73a49}.fab-modal{display:none;position:fixed;inset:0;background:rgba(16,27,51,.52);z-index:9999;align-items:center;justify-content:center}.fab-modal.open{display:flex}.fab-modal-card{max-width:520px;width:100%;background:#fff;border-radius:6px;padding:14px;border:1px solid #d8dfeb}.fab-msg{background:#e7f5ec;color:#1f6b40;border:1px solid #a6d7ba;border-radius:3px;padding:7px 9px;font-size:11px;margin-bottom:8px}.jupiterx-footer{display:none !important}.qlwapp,.qlwapp__container{display:none !important}body.page-id-35133 #jupiterx-main,body.page-id-35134 #jupiterx-main,body.page-id-35135 #jupiterx-main,body.page-id-35136 #jupiterx-main,body.page-id-35137 #jupiterx-main,body.page-id-35138 #jupiterx-main{padding-top:220px !important} body.fab-page-active.jupiterx-header-overlapped #jupiterx-main,body.fab-page-active.jupiterx-header-overlapped-tablet #jupiterx-main,body.fab-page-active.jupiterx-header-overlapped-mobile #jupiterx-main{padding-top:calc(var(--fab-header-h,160px) + 12px) !important}@media (max-width:1024px){.fab-grid{grid-template-columns:1fr}.fab-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (max-width:640px){.fab-cards{grid-template-columns:1fr}}');
	wp_register_script('fab-inline', '', array(), '1.0', true); wp_enqueue_script('fab-inline');
	wp_add_inline_script('fab-inline', '(function(){var hasFab=document.querySelector(".fab, .fab-login"); if(hasFab){document.body.classList.add("fab-page-active");} function setHeaderOffset(){ if(!document.body.classList.contains("fab-page-active")){return;} var h=document.querySelector(".jupiterx-header"); if(h){document.documentElement.style.setProperty("--fab-header-h", h.offsetHeight+"px");}} setHeaderOffset(); window.addEventListener("resize", setHeaderOffset); var m=document.getElementById("fabModal");var o=document.querySelectorAll(".js-fab-open");var c=document.querySelectorAll(".js-fab-close");for(var i=0;i<o.length;i++){o[i].onclick=function(e){e.preventDefault();if(m){m.classList.add("open");}}}for(var j=0;j<c.length;j++){c[j].onclick=function(){if(m){m.classList.remove("open");}}}if(m){m.onclick=function(e){if(e.target===m){m.classList.remove("open");}}}})();');
}
add_action('wp_enqueue_scripts', 'fab_assets');

function fab_user_menu_shortcode() {
	if (is_user_logged_in()) {
		$user = wp_get_current_user();
		$name = $user && !empty($user->display_name) ? $user->display_name : 'Perfil';
		return '<span style="font-size:11px;color:#ffffff;background:#173975;border-radius:14px;padding:4px 10px;display:inline-block;">' . esc_html($name) . '</span>';
	}
	return '<a href="' . esc_url(fab_page_url('fabricamos-login/')) . '" style="font-size:11px;color:#ffffff;background:#173975;border-radius:14px;padding:4px 10px;display:inline-block;text-decoration:none;">Entrar</a>';
}
add_shortcode('dsf_user_menu', 'fab_user_menu_shortcode');

function fab_filter($items, $setor, $empresa, $substancia) {
	$out = array();
	foreach ($items as $i) {
		if ($setor !== '' && strtolower($i['setor']) !== strtolower($setor)) { continue; }
		if ($empresa !== '' && strpos(strtolower($i['nome']), strtolower($empresa)) === false) { continue; }
		if ($substancia !== '') {
			$ok = false;
			foreach ($i['subs'] as $s) {
				if (strpos(strtolower($s), strtolower($substancia)) !== false) { $ok = true; break; }
			}
			if (!$ok) { continue; }
		}
		$out[] = $i;
	}
	return $out;
}

function fab_setores() {
	$setores = array();
	foreach (fab_data() as $i) { if (!in_array($i['setor'], $setores, true)) { $setores[] = $i['setor']; } }
	sort($setores);
	return $setores;
}

function fab_page_url($path) { return home_url('/' . ltrim($path, '/')); }

function fab_catalogo_shortcode($atts) {
	$a = shortcode_atts(array('mode' => 'comprador'), $atts, 'fabricamos_catalogo');
	$mode = strtolower(trim($a['mode']));
	$items = fab_data();
	$setor = isset($_GET['setor']) ? sanitize_text_field(wp_unslash($_GET['setor'])) : '';
	$empresa = isset($_GET['empresa']) ? sanitize_text_field(wp_unslash($_GET['empresa'])) : '';
	$substancia = isset($_GET['substancia']) ? sanitize_text_field(wp_unslash($_GET['substancia'])) : '';
	$filtered = fab_filter($items, $setor, $empresa, $substancia);
	$pg = isset($_GET['pg']) ? max(1, (int) $_GET['pg']) : 1;
	$pp = 6;
	$total = count($filtered);
	$pages = max(1, (int) ceil($total / $pp));
	$rows = array_slice($filtered, ($pg - 1) * $pp, $pp);
	$slug = isset($_GET['fabricante']) ? sanitize_title(wp_unslash($_GET['fabricante'])) : '';
	$sel = $slug ? fab_find($slug) : (isset($rows[0]) ? $rows[0] : fab_data()[0]);
	ob_start(); ?>
	<div class="fab">
		<?php echo fab_top(); ?>
		<div class="fab-grid">
			<div class="fab-box">
				<div class="fab-title">Filtros</div>
				<form method="get">
					<select class="fab-field" name="setor"><option value="">Setor</option><?php foreach (fab_setores() as $s) { echo '<option value="'.esc_attr($s).'" '.selected($setor, $s, false).'>'.esc_html($s).'</option>'; } ?></select>
					<input class="fab-field" type="text" name="empresa" value="<?php echo esc_attr($empresa); ?>" placeholder="Pesquise o nome da Empresa">
					<input class="fab-field" type="text" name="substancia" value="<?php echo esc_attr($substancia); ?>" placeholder="Pesquise o nome da Substancia">
					<button class="fab-btn" type="submit">Pesquisar</button>
				</form>
				<div class="fab-title" style="margin-top:10px;">Detalhes</div>
				<?php if ($mode === 'fabricante') { ?>
					<a class="fab-btn" href="<?php echo esc_url(fab_page_url('meu-fabricante/?fabricante=' . $sel['slug'])); ?>">Editar meu fabricante</a>
				<?php } else { ?>
					<a class="fab-btn js-fab-open" href="#">Quero anunciar minha empresa</a>
				<?php } ?>
			</div>
			<div class="fab-box">
				<div class="fab-title">Associados</div>
				<div class="fab-cards">
					<?php foreach ($rows as $i) { ?>
						<div class="fab-card"><a href="<?php echo esc_url(fab_page_url('detalhes-do-fabricante/?fabricante=' . $i['slug'])); ?>"><img src="<?php echo esc_url($i['logo']); ?>" alt="<?php echo esc_attr($i['nome']); ?>"></a></div>
					<?php } ?>
				</div>
				<div class="fab-pg">
					<?php for ($i = 1; $i <= $pages; $i++) { $u = add_query_arg(array('pg'=>$i,'setor'=>$setor,'empresa'=>$empresa,'substancia'=>$substancia)); echo '<a class="'.($i===$pg?'on':'').'" href="'.esc_url($u).'">'.$i.'</a>'; } ?>
					<em>Pagina <?php echo esc_html($pg); ?> de <?php echo esc_html($pages); ?></em>
				</div>
			</div>
			<div class="fab-box">
				<div class="fab-title">Outros fabricantes</div>
				<ul class="fab-list"><?php foreach ($items as $i) { echo '<li><a href="'.esc_url(fab_page_url('detalhes-do-fabricante/?fabricante='.$i['slug'])).'">'.esc_html(strtolower($i['nome'])).'</a></li>'; } ?></ul>
				<div class="fab-title" style="margin-top:10px;"><?php echo esc_html($sel['nome']); ?></div>
				<p style="font-size:11px;line-height:1.45;"><?php echo esc_html($sel['descricao']); ?></p>
				<a class="fab-btn" href="<?php echo esc_url('https://' . preg_replace('#^https?://#', '', $sel['site'])); ?>" target="_blank" rel="noopener">Acessar o site da Empresa</a>
			</div>
		</div>
	</div>
	<div id="fabModal" class="fab-modal"><div class="fab-modal-card"><h4 style="margin:0 0 10px;color:#132d60;">Preencha para continuar</h4><div class="fab-row"><input class="fab-field" placeholder="Nome"><input class="fab-field" placeholder="Cargo na empresa"></div><div class="fab-row"><input class="fab-field" placeholder="Telefone"><input class="fab-field" placeholder="E-mail"></div><button class="fab-btn js-fab-close" type="button">Continuar</button></div></div>
	<?php return ob_get_clean();
}
add_shortcode('fabricamos_catalogo', 'fab_catalogo_shortcode');

function fab_detalhes_shortcode() {
	$slug = isset($_GET['fabricante']) ? sanitize_title(wp_unslash($_GET['fabricante'])) : 'endogen';
	$i = fab_find($slug);
	ob_start(); ?>
	<div class="fab">
		<?php echo fab_top(); ?>
		<div class="fab-box">
			<div class="fab-detailhead"><a class="fab-back" href="<?php echo esc_url(fab_page_url('')); ?>">&lt;</a><div class="fab-h"><?php echo esc_html($i['nome']); ?></div><a class="fab-btn" style="width:auto;padding:7px 10px;" href="<?php echo esc_url(fab_page_url('meu-fabricante/?fabricante=' . $i['slug'])); ?>">Editar Informacoes</a></div>
			<div class="fab-logo"><img src="<?php echo esc_url($i['logo']); ?>" alt="<?php echo esc_attr($i['nome']); ?>"></div>
			<div class="fab-text"><?php echo esc_html($i['descricao']); ?></div>
			<div class="fab-contact" style="margin-top:10px;">
				<div class="fab-row"><div><span class="fab-label">Nome/Departamento:</span><input class="fab-field" value="<?php echo esc_attr($i['contato']); ?>" readonly></div><div></div></div>
				<div class="fab-row"><div><span class="fab-label">Telefone:</span><input class="fab-field" value="<?php echo esc_attr($i['telefone']); ?>" readonly></div><div><span class="fab-label">E-mail:</span><input class="fab-field" value="<?php echo esc_attr($i['email']); ?>" readonly></div></div>
				<span class="fab-label">Site:</span><input class="fab-field" value="<?php echo esc_attr($i['site']); ?>" readonly>
				<a class="fab-btn" href="<?php echo esc_url('https://' . preg_replace('#^https?://#', '', $i['site'])); ?>" target="_blank" rel="noopener">Acessar o site da Empresa</a>
			</div>
			<ul class="fab-subs"><?php foreach ($i['subs'] as $s) { echo '<li>'.esc_html($s).'<span>&gt;</span></li>'; } ?></ul>
		</div>
	</div>
	<?php return ob_get_clean();
}
add_shortcode('fabricamos_detalhes', 'fab_detalhes_shortcode');

function fab_meu_fabricante_shortcode() {
	$slug = isset($_GET['fabricante']) ? sanitize_title(wp_unslash($_GET['fabricante'])) : 'endogen';
	$i = fab_find($slug);
	$saved = false;
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fab_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fab_nonce'])), 'fab_edit')) { $saved = true; }
	ob_start(); ?>
	<div class="fab">
		<?php echo fab_top(); ?>
		<div class="fab-box">
			<?php if ($saved) { ?><div class="fab-msg">Edicao salva (modo visual de referencia).</div><?php } ?>
			<div class="fab-detailhead"><a class="fab-back" href="<?php echo esc_url(fab_page_url('detalhes-do-fabricante/?fabricante=' . $i['slug'])); ?>">&lt;</a><div class="fab-h">Editar - Permissao</div></div>
			<form method="post">
				<?php wp_nonce_field('fab_edit', 'fab_nonce'); ?>
				<div class="fab-contact">
					<div class="fab-row"><div><span class="fab-label">Nome/Departamento:</span><input class="fab-field" name="contato" value="<?php echo esc_attr($i['contato']); ?>"></div><div></div></div>
					<div class="fab-row"><div><span class="fab-label">Telefone:</span><input class="fab-field" name="telefone" value="<?php echo esc_attr($i['telefone']); ?>"></div><div><span class="fab-label">E-mail:</span><input class="fab-field" name="email" value="<?php echo esc_attr($i['email']); ?>"></div></div>
				</div>
				<div class="fab-title" style="margin-top:10px;"><?php echo esc_html($i['nome']); ?></div>
				<div class="fab-logo"><img src="<?php echo esc_url($i['logo']); ?>" alt="<?php echo esc_attr($i['nome']); ?>"></div>
				<div class="fab-contact">
					<span class="fab-label">Descricao:</span><textarea class="fab-field" style="height:70px;padding:8px;resize:vertical;"><?php echo esc_textarea($i['descricao']); ?></textarea>
					<div class="fab-row"><div><span class="fab-label">Nome/Departamento:</span><input class="fab-field" value="<?php echo esc_attr($i['contato']); ?>"></div><div></div></div>
					<div class="fab-row"><div><span class="fab-label">Telefone:</span><input class="fab-field" value="<?php echo esc_attr($i['telefone']); ?>"></div><div><span class="fab-label">E-mail:</span><input class="fab-field" value="<?php echo esc_attr($i['email']); ?>"></div></div>
					<span class="fab-label">Site:</span><input class="fab-field" value="<?php echo esc_attr($i['site']); ?>">
				</div>
				<div class="fab-contact" style="margin-top:10px;">
					<span class="fab-label">Pesquisar Insumo:</span><input class="fab-field" placeholder="Dig...">
					<ul class="fab-subs"><?php foreach ($i['subs'] as $s) { echo '<li>'.esc_html($s).'<span>x</span></li>'; } ?></ul>
					<button class="fab-btn" type="submit" style="margin-top:10px;">Salvar Edicoes</button>
				</div>
			</form>
		</div>
	</div>
	<?php return ob_get_clean();
}
add_shortcode('fabricamos_meu_fabricante', 'fab_meu_fabricante_shortcode');

function fab_painel_shortcode() {
	$q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
	$items = $q !== '' ? fab_filter(fab_data(), '', $q, '') : fab_data();
	ob_start(); ?>
	<div class="fab">
		<?php echo fab_top(); ?>
		<div class="fab-box">
			<div class="fab-detailhead"><a class="fab-back" href="<?php echo esc_url(fab_page_url('')); ?>">&lt;</a><div class="fab-h">Empresas</div></div>
			<form method="get" style="display:flex;gap:8px;align-items:center;margin:8px 0;">
				<input class="fab-field" style="margin:0;flex:1;" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Pesquisar fabricante">
				<a class="fab-btn" style="width:auto;padding:7px 10px;" href="<?php echo esc_url(fab_page_url('meu-fabricante/')); ?>">Criar Fabricante</a>
				<button class="fab-btn" style="width:auto;padding:7px 10px;" type="button">Exportar Excel</button>
				<a class="fab-btn" style="width:auto;padding:7px 10px;" href="<?php echo esc_url(fab_page_url('painel-gerencial/')); ?>">Limpar filtros</a>
			</form>
			<div class="fab-tablewrap" style="overflow:auto;">
				<table class="fab-table">
					<thead><tr><th>Empresa</th><th>Associado</th><th>Processo</th><th>Origem</th><th>Insumo</th><th>DCB</th><th>INN</th><th>CAS</th><th>NCM</th><th>Certificado</th><th>Nome</th><th>Telefone</th><th>Email</th><th>Acoes</th></tr></thead>
					<tbody>
					<?php foreach ($items as $i) { ?>
						<tr><td><?php echo esc_html($i['nome']); ?></td><td><?php echo esc_html($i['associado']); ?></td><td><?php echo esc_html($i['setor']); ?></td><td><?php echo esc_html($i['origem']); ?></td><td><?php echo esc_html($i['insumo']); ?></td><td><?php echo esc_html($i['dcb']); ?></td><td><?php echo esc_html($i['inn']); ?></td><td><?php echo esc_html($i['cas']); ?></td><td><?php echo esc_html($i['ncm']); ?></td><td><?php echo esc_html($i['certificado']); ?></td><td><?php echo esc_html($i['contato']); ?></td><td><?php echo esc_html($i['telefone']); ?></td><td><?php echo esc_html($i['email']); ?></td><td><span class="fab-acts"><span class="fab-dot fab-g"></span><span class="fab-dot fab-r"></span><a href="<?php echo esc_url(fab_page_url('meu-fabricante/?fabricante=' . $i['slug'])); ?>">Editar</a></span></td></tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
			<div class="fab-pg"><span class="on">1</span><a href="#">2</a><a href="#">3</a><a href="#">4</a><a href="#">5</a><em>Pagina 1 de 25</em></div>
		</div>
	</div>
	<?php return ob_get_clean();
}
add_shortcode('fabricamos_painel', 'fab_painel_shortcode');

function fab_login_shortcode() {
	ob_start(); ?>
	<div class="fab-login">
		<?php echo fab_top(); ?>
		<div class="fab-box" style="padding:20px 24px;">
			<h2>Log In</h2>
			<?php wp_login_form(array('label_username'=>'Nome de Usuario ou Endereco de e-mail','label_password'=>'Senha','label_remember'=>'Lembre de mim','label_log_in'=>'Login','remember'=>true)); ?>
		</div>
	</div>
	<?php return ob_get_clean();
}
add_shortcode('fabricamos_login', 'fab_login_shortcode');


