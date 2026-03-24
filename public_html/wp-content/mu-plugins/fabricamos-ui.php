<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fab_page_url( $path ) {
	return home_url( '/' . ltrim( $path, '/' ) );
}

function fab_data() {
	// Base limpa: nenhuma empresa exibida nesta etapa.
	return array();
}

function fab_default_item() {
	return array(
		'slug' => 'empresa',
		'nome' => 'Empresa',
		'logo' => '',
		'descricao' => '',
		'setor' => '',
		'contato' => '',
		'telefone' => '',
		'email' => '',
		'site' => '',
		'subs' => array(),
	);
}

function fab_find( $slug ) {
	foreach ( fab_data() as $item ) {
		if ( isset( $item['slug'] ) && $item['slug'] === $slug ) {
			return $item;
		}
	}
	$all = fab_data();
	return ! empty( $all ) ? $all[0] : fab_default_item();
}

function fab_setores() {
	$out = array();
	foreach ( fab_data() as $i ) {
		if ( ! empty( $i['setor'] ) && ! in_array( $i['setor'], $out, true ) ) {
			$out[] = $i['setor'];
		}
	}
	sort( $out );
	return $out;
}

function fab_filter( $items, $setor, $empresa, $substancia ) {
	$out = array();
	foreach ( $items as $i ) {
		if ( $setor !== '' && isset( $i['setor'] ) && strtolower( $i['setor'] ) !== strtolower( $setor ) ) { continue; }
		if ( $empresa !== '' && isset( $i['nome'] ) && strpos( strtolower( $i['nome'] ), strtolower( $empresa ) ) === false ) { continue; }
		if ( $substancia !== '' ) {
			$ok = false;
			if ( isset( $i['subs'] ) && is_array( $i['subs'] ) ) {
				foreach ( $i['subs'] as $s ) {
					if ( strpos( strtolower( $s ), strtolower( $substancia ) ) !== false ) { $ok = true; break; }
				}
			}
			if ( ! $ok ) { continue; }
		}
		$out[] = $i;
	}
	return $out;
}

function fab_gate_modal( $title ) {
	ob_start();
	?>
	<div class="fab-gate-overlay is-open">
		<div class="fab-gate-card">
			<h3><?php echo esc_html( $title ); ?></h3>
			<p>Preencha para continuar.</p>
			<form action="<?php echo esc_url( fab_page_url( 'log-in/' ) ); ?>" method="get">
				<div class="fab-row">
					<input class="fab-field" type="text" name="nome" placeholder="Nome" required>
					<input class="fab-field" type="text" name="cargo" placeholder="Cargo na empresa" required>
				</div>
				<div class="fab-row">
					<input class="fab-field" type="text" name="telefone" placeholder="Telefone" required>
					<input class="fab-field" type="email" name="email" placeholder="E-mail" required>
				</div>
				<div class="fab-row">
					<button class="fab-btn" type="submit">Entrar</button>
					<a class="fab-btn fab-btn-outline" href="<?php echo esc_url( fab_page_url( 'cadastro/' ) ); ?>">Cadastro</a>
				</div>
			</form>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function fab_inline_css() {
	return ':root{--fab-header-height:110px}
body.fab-page-active #jupiterx-main{
padding-top:var(--fab-header-height)!important;
margin:0!important;
position:relative!important;
left:50%!important;
right:50%!important;
margin-left:-50vw!important;
margin-right:-50vw!important;
width:100vw!important;
max-width:100vw!important
}
body.fab-page-active .jupiterx-main-content,
body.fab-page-active .jupiterx-post,
body.fab-page-active .jupiterx-post-content,
body.fab-page-active article,
body.fab-page-active .entry-content{
padding-top:0!important;
margin:0!important;
max-width:none!important;
width:100%!important
}
body.fab-page-active #jupiterx-main>:not(.fab-shell):not(.fab-gate-overlay):not(#fabModal){display:none!important}
body.fab-page-active .jupiterx-footer,.qlwapp,.qlwapp__container{display:none!important}
body.fab-page-active .dsf-user-menu{position:static!important;top:auto!important;right:auto!important;z-index:auto!important;white-space:nowrap!important}
body.fab-page-active .dsf-user-menu *{white-space:nowrap!important}
.fab-shell{width:calc(100vw - 36px)!important;max-width:calc(100vw - 36px)!important;margin:12px auto 24px!important;min-height:calc(100vh - var(--fab-header-height) - 30px)}
.fab{background:#f1f3f5;border:1px solid #dde2ea;border-radius:8px;padding:14px;font-family:"Poppins","Segoe UI",Arial,sans-serif}
.fab-grid{display:grid;grid-template-columns:320px minmax(0,1fr);gap:14px;align-items:start}
.fab-box{background:#fff;border:1px solid #dde2ea;border-radius:6px;padding:10px}
.fab-title{font-size:28px;font-weight:700;color:#132d60;margin:0 0 12px}
.fab-subtitle{font-size:19px;font-weight:700;color:#132d60;margin:0 0 8px;display:flex;gap:6px;align-items:center}
.fab-subtitle:after{content:"";height:2px;background:#2f6fd6;flex:1}
.fab-field{width:100%;height:42px;border:1px solid #d5dbe5;border-radius:4px;padding:0 10px;font-size:19px;margin-bottom:8px;background:#f9fafc}
.fab-btn{display:block;background:#132d60;color:#fff;text-align:center;border-radius:4px;padding:11px;font-size:19px;line-height:1;text-decoration:none;border:none;cursor:pointer}
.fab-btn:hover{background:#1b3f80;color:#fff}
.fab-btn-outline{background:#fff;color:#132d60;border:1px solid #132d60}
.fab-btn:disabled{opacity:.7;cursor:not-allowed}
.fab-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;min-height:280px}
.fab-card{background:#fff;border:1px solid #dde2ea;border-radius:8px;min-height:108px;display:flex;align-items:center;justify-content:center;padding:10px}
.fab-card a{color:#1e4e9f;text-decoration:none;font-size:16px}
.fab-card img{max-width:170px;max-height:60px}
.fab-card-empty{background:linear-gradient(90deg,#f1f3f6,#e8ecf2,#f1f3f6);background-size:200% 100%;animation:fabpulse 1.6s linear infinite}
@keyframes fabpulse{0%{background-position:0 0}100%{background-position:-200% 0}}
.fab-empty-note{font-size:16px;color:#5b6782;margin-top:8px}
.fab-main-list{min-height:470px;display:flex;flex-direction:column}
.fab-pg{display:flex;gap:4px;margin-top:8px;align-items:center}
.fab-pg a,.fab-pg span{font-size:13px;min-width:22px;height:22px;line-height:22px;text-align:center;background:#e7ebf0;border-radius:2px;text-decoration:none;color:#132d60}
.fab-pg .on{background:#132d60;color:#fff}
.fab-pg em{margin-left:auto;font-size:13px;color:#697489;font-style:normal}
.fab-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.fab-gate-overlay{display:none;position:fixed;inset:0;background:rgba(16,27,51,.52);z-index:10000;align-items:center;justify-content:center;padding:20px}
.fab-gate-overlay.is-open{display:flex}
.fab-gate-card{max-width:620px;width:100%;background:#fff;border-radius:6px;padding:16px;border:1px solid #d8dfeb}
.fab-gate-card h3{margin:0 0 8px;font-size:28px;color:#132d60}
.fab-gate-card p{margin:0 0 12px;font-size:16px;color:#4b5a79}
.fab-login{max-width:820px;margin:16px auto 30px;background:#f1f3f5;padding:12px;border-radius:6px;font-family:"Poppins","Segoe UI",Arial,sans-serif}
.fab-login h2{margin:0 0 14px;font-size:31px;color:#132d60}
.fab-login input[type=text],.fab-login input[type=password],.fab-login input[type=email]{width:100%;height:40px;border:1px solid #d5dbe5;border-radius:4px;padding:0 8px;background:#f9fafc;font-size:14px}
.fab-login label{font-size:14px;color:#3d4862}
.fab-login #wp-submit{background:#132d60;color:#fff;border:none;border-radius:4px;padding:10px 14px;font-size:14px;cursor:pointer}
.fab-login-links{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.fab-login-links a{font-size:13px;color:#2f5fb3;text-decoration:none}
.fab-msg{background:#e7f5ec;color:#1f6b40;border:1px solid #a6d7ba;border-radius:3px;padding:7px 9px;font-size:12px;margin-bottom:8px}
@media (max-width:1180px){.fab-title{font-size:24px}.fab-subtitle,.fab-field,.fab-btn{font-size:15px}.fab-grid{grid-template-columns:1fr}.fab-cards{grid-template-columns:repeat(2,minmax(0,1fr));min-height:0}.fab-main-list{min-height:0}}
@media (max-width:640px){.fab-cards{grid-template-columns:1fr}.fab-row{grid-template-columns:1fr}.fab-shell{width:calc(100vw - 14px)!important;max-width:calc(100vw - 14px)!important}}';
}

function fab_body_class( $classes ) {
	if ( ! is_singular() ) {
		return $classes;
	}
	global $post;
	if ( ! $post ) {
		return $classes;
	}
	$tags = array( 'fabricamos_catalogo', 'fabricamos_detalhes', 'fabricamos_meu_fabricante', 'fabricamos_painel', 'fabricamos_login', 'fabricamos_cadastro', 'fabricamos_profile', 'fabricamos_account' );
	foreach ( $tags as $tag ) {
		if ( has_shortcode( $post->post_content, $tag ) ) {
			$classes[] = 'fab-page-active';
			break;
		}
	}
	return $classes;
}
add_filter( 'body_class', 'fab_body_class' );

function fab_critical_style_tag() {
	return '<style id="fab-critical-inline">'
		. 'body.fab-page-active #jupiterx-main{position:relative!important;left:50%!important;right:50%!important;margin-left:-50vw!important;margin-right:-50vw!important;width:100vw!important;max-width:100vw!important;padding-top:var(--fab-header-height)!important;}'
		. 'body.fab-page-active #jupiterx-main>:not(.fab-shell):not(.fab-gate-overlay):not(#fabModal){display:none!important;}'
		. 'body.fab-page-active .dsf-user-menu{position:static!important;top:auto!important;right:auto!important;z-index:auto!important;white-space:nowrap!important;}'
		. 'body.fab-page-active .dsf-user-menu *{white-space:nowrap!important;}'
		. '.fab-shell{width:calc(100vw - 36px)!important;max-width:calc(100vw - 36px)!important;}'
		. '</style>';
}

function fab_assets() {
	if ( ! is_singular() ) { return; }
	global $post;
	if ( ! $post ) { return; }
	$tags = array( 'fabricamos_catalogo', 'fabricamos_detalhes', 'fabricamos_meu_fabricante', 'fabricamos_painel', 'fabricamos_login', 'fabricamos_cadastro', 'fabricamos_profile', 'fabricamos_account' );
	$ok = false;
	foreach ( $tags as $t ) { if ( has_shortcode( $post->post_content, $t ) ) { $ok = true; break; } }
	if ( ! $ok ) { return; }
	wp_register_style( 'fab-inline', false, array(), '3.3' );
	wp_enqueue_style( 'fab-inline' );
	wp_add_inline_style( 'fab-inline', fab_inline_css() );
	wp_register_script( 'fab-inline', '', array(), '3.3', true );
	wp_enqueue_script( 'fab-inline' );
	wp_add_inline_script( 'fab-inline', '(function(){var has=document.querySelector(".fab,.fab-login,.fab-shell");if(!has){return;}document.body.classList.add("fab-page-active");function offs(){var h=document.querySelector(".jupiterx-header");var s=h?Math.ceil(h.getBoundingClientRect().height):0;s=Math.min(118,s);document.documentElement.style.setProperty("--fab-header-height",s+"px");}offs();window.addEventListener("resize",offs);}())' );
}
add_action( 'wp_enqueue_scripts', 'fab_assets' );

function fab_link_if_exists( $slug, $fallback ) {
	$page = get_page_by_path( trim( $slug, '/' ) );
	if ( $page instanceof WP_Post && $page->post_status === 'publish' ) {
		return get_permalink( $page->ID );
	}
	return $fallback;
}

function fab_user_menu_shortcode() {
	$login_url = fab_link_if_exists( 'log-in', wp_login_url( fab_page_url( 'fabricamos/' ) ) );
	$register_url = fab_link_if_exists( 'cadastro', fab_page_url( 'cadastro/' ) );
	$profile_url = fab_link_if_exists( 'profile', admin_url( 'profile.php' ) );
	$pill = 'font-size:11px;color:#ffffff;background:#173975;border:1px solid #3f66b6;border-radius:14px;padding:4px 10px;display:inline-block;text-decoration:none;line-height:1.3;white-space:nowrap;';
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$name = $user && ! empty( $user->display_name ) ? $user->display_name : 'Perfil';
		$logout = wp_logout_url( fab_page_url( 'fabricamos/' ) );
		$name_style = $pill . 'max-width:120px;overflow:hidden;text-overflow:ellipsis;';
		return '<div style="display:flex;gap:6px;align-items:center;justify-content:flex-end;">'
			. '<a href="' . esc_url( $profile_url ) . '" style="' . esc_attr( $name_style ) . '" title="' . esc_attr( $name ) . '">' . esc_html( $name ) . '</a>'
			. '<a href="' . esc_url( $logout ) . '" style="' . esc_attr( $pill ) . '">Sair</a>'
			. '</div>';
	}
	$primary = 'font-size:11px;color:#132d60;background:#9fb8ff;border:1px solid #b5c8ff;border-radius:14px;padding:4px 10px;display:inline-block;text-decoration:none;line-height:1.3;white-space:nowrap;';
	return '<div style="display:flex;gap:6px;align-items:center;justify-content:flex-end;">' . '<a href="' . esc_url( $login_url ) . '" style="' . esc_attr( $pill ) . '">Entrar</a>' . '<a href="' . esc_url( $register_url ) . '" style="' . esc_attr( $primary ) . '">Cadastro</a>' . '</div>';
}
add_shortcode( 'dsf_user_menu', 'fab_user_menu_shortcode' );

function fab_catalogo_shortcode( $atts ) {
	$a = shortcode_atts( array( 'mode' => 'comprador' ), $atts, 'fabricamos_catalogo' );
	$mode = strtolower( trim( $a['mode'] ) );
	$setor = isset( $_GET['setor'] ) ? sanitize_text_field( wp_unslash( $_GET['setor'] ) ) : '';
	$empresa = isset( $_GET['empresa'] ) ? sanitize_text_field( wp_unslash( $_GET['empresa'] ) ) : '';
	$substancia = isset( $_GET['substancia'] ) ? sanitize_text_field( wp_unslash( $_GET['substancia'] ) ) : '';
	if ( ! is_user_logged_in() ) {
		ob_start();
		?>
		<?php echo fab_critical_style_tag(); ?>
		<section class="fab-shell"><div class="fab"><h1 class="fab-title">Fabricamos</h1><div class="fab-grid"><div class="fab-box"><div class="fab-subtitle">Filtros</div><select class="fab-field" disabled><option>Setor</option></select><input class="fab-field" placeholder="Pesquise o nome da Empresa" disabled><input class="fab-field" placeholder="Pesquise o nome da Substancia" disabled><button class="fab-btn" type="button" disabled>Pesquisar</button><div class="fab-subtitle" style="margin-top:10px;">Detalhes</div><button class="fab-btn" type="button" disabled>Quero anunciar minha empresa</button></div><div class="fab-box fab-main-list"><div class="fab-subtitle">Fabricantes</div><div class="fab-cards"><div class="fab-card fab-card-empty"></div><div class="fab-card fab-card-empty"></div><div class="fab-card fab-card-empty"></div><div class="fab-card fab-card-empty"></div><div class="fab-card fab-card-empty"></div><div class="fab-card fab-card-empty"></div></div><p class="fab-empty-note">Faca login para visualizar os fabricantes.</p></div></div></div></section>
		<?php echo fab_gate_modal( 'Preencha para continuar' ); ?>
		<?php
		return ob_get_clean();
	}
	$items = fab_filter( fab_data(), $setor, $empresa, $substancia );
	$pg = isset( $_GET['pg'] ) ? max( 1, (int) $_GET['pg'] ) : 1;
	$pp = 6;
	$total = count( $items );
	$pages = max( 1, (int) ceil( $total / $pp ) );
	$rows = array_slice( $items, ( $pg - 1 ) * $pp, $pp );
	$slug = isset( $_GET['fabricante'] ) ? sanitize_title( wp_unslash( $_GET['fabricante'] ) ) : '';
	$sel = $slug ? fab_find( $slug ) : ( isset( $rows[0] ) ? $rows[0] : fab_default_item() );
	ob_start();
	?>
	<?php echo fab_critical_style_tag(); ?>
	<section class="fab-shell"><div class="fab"><h1 class="fab-title">Fabricamos</h1><div class="fab-grid"><div class="fab-box"><div class="fab-subtitle">Filtros</div><form method="get"><select class="fab-field" name="setor"><option value="">Setor</option><?php foreach ( fab_setores() as $s ) { echo '<option value="' . esc_attr( $s ) . '" ' . selected( $setor, $s, false ) . '>' . esc_html( $s ) . '</option>'; } ?></select><input class="fab-field" type="text" name="empresa" value="<?php echo esc_attr( $empresa ); ?>" placeholder="Pesquise o nome da Empresa"><input class="fab-field" type="text" name="substancia" value="<?php echo esc_attr( $substancia ); ?>" placeholder="Pesquise o nome da Substancia"><button class="fab-btn" type="submit">Pesquisar</button></form><div class="fab-subtitle" style="margin-top:10px;">Detalhes</div><?php if ( $mode === 'fabricante' ) { ?><a class="fab-btn" href="<?php echo esc_url( fab_page_url( 'meu-fabricante/?fabricante=' . $sel['slug'] ) ); ?>">Editar meu fabricante</a><?php } else { ?><a class="fab-btn" href="<?php echo esc_url( fab_page_url( 'cadastro/' ) ); ?>">Quero anunciar minha empresa</a><?php } ?></div><div class="fab-box fab-main-list"><div class="fab-subtitle">Fabricantes</div><?php if ( empty( $rows ) ) { ?><div class="fab-cards"><div class="fab-card fab-card-empty"></div><div class="fab-card fab-card-empty"></div><div class="fab-card fab-card-empty"></div></div><p class="fab-empty-note">Nenhuma empresa cadastrada.</p><?php } else { ?><div class="fab-cards"><?php foreach ( $rows as $i ) { ?><div class="fab-card"><a href="<?php echo esc_url( fab_page_url( 'detalhes-do-fabricante/?fabricante=' . $i['slug'] ) ); ?>"><?php if ( ! empty( $i['logo'] ) ) { ?><img src="<?php echo esc_url( $i['logo'] ); ?>" alt="<?php echo esc_attr( $i['nome'] ); ?>"><?php } else { echo esc_html( $i['nome'] ); } ?></a></div><?php } ?></div><?php } ?><div class="fab-pg"><?php for ( $i = 1; $i <= $pages; $i++ ) { $u = add_query_arg( array( 'pg' => $i, 'setor' => $setor, 'empresa' => $empresa, 'substancia' => $substancia ) ); echo '<a class="' . ( $i === $pg ? 'on' : '' ) . '" href="' . esc_url( $u ) . '">' . $i . '</a>'; } ?><em>Pagina <?php echo esc_html( $pg ); ?> de <?php echo esc_html( $pages ); ?></em></div></div></div></div></section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'fabricamos_catalogo', 'fab_catalogo_shortcode' );

function fab_detalhes_shortcode() {
	if ( ! is_user_logged_in() ) { return fab_gate_modal( 'Preencha para continuar' ); }
	$i = isset( $_GET['fabricante'] ) ? fab_find( sanitize_title( wp_unslash( $_GET['fabricante'] ) ) ) : fab_default_item();
	ob_start();
	?>
	<div class="fab-login"><div class="fab-box"><h2 style="font-size:20px;margin:0 0 8px;color:#132d60;">Detalhes do Fabricante</h2><p style="font-size:12px;margin:0 0 8px;"><strong><?php echo esc_html( $i['nome'] ); ?></strong></p><p style="font-size:12px;margin:0;">Sem dados detalhados nesta base limpa.</p></div></div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'fabricamos_detalhes', 'fab_detalhes_shortcode' );

function fab_meu_fabricante_shortcode() {
	if ( ! is_user_logged_in() ) { return fab_gate_modal( 'Preencha para continuar' ); }
	return '<div class="fab-login"><div class="fab-box"><h2 style="font-size:20px;margin:0 0 8px;color:#132d60;">Meu Fabricante</h2><p style="font-size:12px;margin:0;">Area pronta para edicao quando houver dados de fabricantes.</p></div></div>';
}
add_shortcode( 'fabricamos_meu_fabricante', 'fab_meu_fabricante_shortcode' );

function fab_painel_shortcode() {
	if ( ! is_user_logged_in() ) { return fab_gate_modal( 'Preencha para continuar' ); }
	return '<div class="fab-login"><div class="fab-box"><h2 style="font-size:20px;margin:0 0 8px;color:#132d60;">Painel Gerencial</h2><p style="font-size:12px;margin:0;">Nenhuma empresa cadastrada nesta base.</p></div></div>';
}
add_shortcode( 'fabricamos_painel', 'fab_painel_shortcode' );

function fab_login_shortcode() {
	ob_start();
	?>
	<div class="fab-login"><div class="fab-box" style="padding:20px 24px;"><h2>Log In</h2><?php if ( is_user_logged_in() ) { ?><div class="fab-msg">Voce ja esta logado. <a href="<?php echo esc_url( fab_page_url( 'fabricamos/' ) ); ?>">Ir para Fabricamos</a></div><?php } else { ?><?php wp_login_form( array( 'redirect' => fab_page_url( 'fabricamos/' ), 'label_username' => 'Nome de Usuario ou Endereco de e-mail', 'label_password' => 'Senha', 'label_remember' => 'Lembre de mim', 'label_log_in' => 'Login', 'remember' => true ) ); ?><div class="fab-login-links"><a href="<?php echo esc_url( fab_page_url( 'cadastro/' ) ); ?>">Criar cadastro</a><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Esqueci minha senha</a></div><?php } ?></div></div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'fabricamos_login', 'fab_login_shortcode' );

function fab_cadastro_shortcode() {
	$errors = array();
	$success = '';
	if ( is_user_logged_in() ) {
		return '<div class="fab-login"><div class="fab-box" style="padding:20px 24px;"><div class="fab-msg">Voce ja esta logado. <a href="' . esc_url( fab_page_url( 'fabricamos/' ) ) . '">Ir para Fabricamos</a></div></div></div>';
	}
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fab_register_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fab_register_nonce'] ) ), 'fab_register' ) ) {
		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ), true ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$display = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		if ( $display === '' ) { $errors[] = 'Informe seu nome.'; }
		if ( $username === '' || strlen( $username ) < 4 ) { $errors[] = 'Usuario invalido.'; }
		if ( ! is_email( $email ) ) { $errors[] = 'E-mail invalido.'; }
		if ( strlen( $password ) < 6 ) { $errors[] = 'Senha deve ter ao menos 6 caracteres.'; }
		if ( username_exists( $username ) ) { $errors[] = 'Usuario ja cadastrado.'; }
		if ( email_exists( $email ) ) { $errors[] = 'E-mail ja cadastrado.'; }
		if ( empty( $errors ) ) {
			$user_id = wp_create_user( $username, $password, $email );
			if ( is_wp_error( $user_id ) ) {
				$errors[] = $user_id->get_error_message();
			} else {
				wp_update_user( array( 'ID' => $user_id, 'display_name' => $display, 'nickname' => $display ) );
				$success = 'Cadastro realizado. Agora faca login para continuar.';
			}
		}
	}
	ob_start();
	?>
	<div class="fab-login"><div class="fab-box" style="padding:20px 24px;"><h2>Cadastro</h2><?php if ( ! empty( $success ) ) { ?><div class="fab-msg"><?php echo esc_html( $success ); ?> <a href="<?php echo esc_url( fab_page_url( 'log-in/' ) ); ?>">Entrar</a></div><?php } ?><?php if ( ! empty( $errors ) ) { foreach ( $errors as $err ) { ?><div style="background:#fdecec;color:#8d1a1a;border:1px solid #f6c7c7;border-radius:4px;padding:8px 10px;margin-bottom:8px;font-size:12px;"><?php echo esc_html( $err ); ?></div><?php }} ?><form method="post"><?php wp_nonce_field( 'fab_register', 'fab_register_nonce' ); ?><p><label for="fab_display_name">Nome</label><input id="fab_display_name" name="display_name" type="text" required></p><p><label for="fab_username">Usuario</label><input id="fab_username" name="username" type="text" required></p><p><label for="fab_email">E-mail</label><input id="fab_email" name="email" type="email" required></p><p><label for="fab_password">Senha</label><input id="fab_password" name="password" type="password" required></p><p><button class="fab-btn" type="submit" style="width:auto;min-width:170px;">Criar conta</button></p></form><div class="fab-login-links"><a href="<?php echo esc_url( fab_page_url( 'log-in/' ) ); ?>">Ja tenho conta</a></div></div></div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'fabricamos_cadastro', 'fab_cadastro_shortcode' );

function fab_profile_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '<div class="fab-login"><div class="fab-box" style="padding:20px 24px;"><div style="font-size:12px;color:#3b4964;">Voce precisa fazer login para acessar seu perfil.</div><div class="fab-login-links"><a href="' . esc_url( fab_page_url( 'log-in/' ) ) . '">Entrar</a></div></div></div>';
	}
	$current = wp_get_current_user();
	$msg = '';
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fab_profile_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fab_profile_nonce'] ) ), 'fab_profile' ) ) {
		$display = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( $display !== '' && is_email( $email ) ) {
			wp_update_user( array( 'ID' => $current->ID, 'display_name' => $display, 'nickname' => $display, 'user_email' => $email ) );
			$current = wp_get_current_user();
			$msg = 'Perfil atualizado.';
		}
	}
	ob_start();
	?>
	<div class="fab-login"><div class="fab-box" style="padding:20px 24px;"><h2>Meu Perfil</h2><?php if ( $msg ) { ?><div class="fab-msg"><?php echo esc_html( $msg ); ?></div><?php } ?><form method="post"><?php wp_nonce_field( 'fab_profile', 'fab_profile_nonce' ); ?><p><label for="fab_profile_display">Nome</label><input id="fab_profile_display" type="text" name="display_name" value="<?php echo esc_attr( $current->display_name ); ?>" required></p><p><label for="fab_profile_email">E-mail</label><input id="fab_profile_email" type="email" name="email" value="<?php echo esc_attr( $current->user_email ); ?>" required></p><p><button class="fab-btn" type="submit" style="width:auto;min-width:170px;">Salvar</button></p></form><div class="fab-login-links"><a href="<?php echo esc_url( fab_page_url( 'fabricamos/' ) ); ?>">Voltar ao Fabricamos</a><a href="<?php echo esc_url( wp_logout_url( fab_page_url( 'fabricamos/' ) ) ); ?>">Sair</a></div></div></div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'fabricamos_profile', 'fab_profile_shortcode' );

function fab_account_shortcode() {
	return fab_profile_shortcode();
}
add_shortcode( 'fabricamos_account', 'fab_account_shortcode' );
