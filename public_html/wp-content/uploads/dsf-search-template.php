<?php
/**
 * Template: DSF – Search Results
 * Mostra resultados de busca com o mesmo cabeçalho/rodapé do tema.
 */

get_header();

// Termo de busca vindo de ?s= (garante que venha “limpo”)
$term = '';
if ( isset( $_GET['s'] ) ) {
    $term = trim( wp_unslash( $_GET['s'] ) );
}

// Paged (funciona tanto /page/2/ quanto ?paged=2)
$paged = get_query_var( 'paged' );
if ( ! $paged ) {
    $paged = get_query_var( 'page' ) ?: 1;
}
$paged = max( 1, (int) $paged );

// Query principal dos resultados
$q = new WP_Query( [
    'post_type'           => 'post',     // ajuste se tiver CPT específico
    's'                   => $term,
    'posts_per_page'      => 12,
    'paged'               => $paged,
    'ignore_sticky_posts' => true,
    'post_status'         => 'publish',
] );
?>

<main id="primary" class="site-main dsf-search">
  <div class="container">

    <!-- Cabeçalho / título da página -->
    <header class="dsf-search__intro">
      <h1 class="dsf-search__title">RESULTADO DE BUSCA AO DICIONÁRIO (DSF)</h1>

      <p>Visualize o acervo e pesquise entre milhares de substâncias farmacêuticas.</p>

      <p>O Dicionário (DSF) reúne informações técnicas padronizadas sobre +14 mil substâncias farmacêuticas, incluindo nomenclaturas como <strong>DCB, INN, CAS, NCM e NVE</strong>, revisadas por especialistas internacionais.</p>

      <p>Use a busca abaixo para explorar uma prévia dos resultados.</p>

      <p>Para visualizar os detalhes completos das substâncias, esteja sempre logado com a <strong>sua conta gratuita</strong>.</p>
    </header>

    <?php if ( $term !== '' ) : ?>
      <section class="dsf-search__term">
        <h2 class="screen-reader-text">Resultados para</h2>
        <p><strong>Resultados para:</strong> <mark><?php echo esc_html( $term ); ?></mark></p>
      </section>
    <?php endif; ?>

    <!-- Lista de resultados -->
    <section class="dsf-search__results">
      <?php if ( $q->have_posts() ) : ?>
        <ul class="dsf-results-list">
          <?php
          while ( $q->have_posts() ) :
            $q->the_post();

            $title   = get_the_title();
            $perma   = get_permalink();
            $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content( null, false ) ), 40, '…' );
          ?>
            <li class="dsf-result">
              <?php if ( is_user_logged_in() ) : ?>
                <h3 class="dsf-result__title">
                  <a href="<?php echo esc_url( $perma ); ?>"><?php echo esc_html( $title ); ?></a>
                </h3>
                <div class="dsf-result__excerpt"><?php echo esc_html( $excerpt ); ?></div>
              <?php else : ?>
                <h3 class="dsf-result__title"><?php echo esc_html( $title ); ?></h3>
                <div class="dsf-result__locked">
                  <small>Faça login para ver os detalhes.</small>
                </div>
              <?php endif; ?>
            </li>
          <?php endwhile; ?>
        </ul>

        <!-- Paginação -->
        <nav class="dsf-pagination" aria-label="Paginação dos resultados">
          <?php
          echo paginate_links( [
            'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
            'format'    => '?paged=%#%',
            'current'   => $paged,
            'total'     => (int) $q->max_num_pages,
            'type'      => 'list',
            'prev_text' => '« Anterior',
            'next_text' => 'Próximo »',

            // garante que o termo da busca continue na URL ao mudar de página
            'add_args'  => [
              's' => $term,
            ],
          ] );
          ?>
        </nav>

      <?php else : ?>
        <p><strong>Nenhum resultado encontrado</strong><?php echo $term ? ' para &quot;' . esc_html( $term ) . '&quot;' : ''; ?>.</p>
      <?php endif; ?>
      <?php wp_reset_postdata(); ?>
    </section>

    <!-- Bloco: Faça uma nova busca (AGORA COM O SHORTCODE) -->
    <section class="dsf-search__new">
      <p>FAÇA UMA NOVA BUSCA NO DSF</p>

      <div class="dsf-search__new-inner">
        <?php echo do_shortcode( '[wpdreams_ajaxsearchlite]' ); ?>
      </div>
    </section>

    <!-- CTA final -->
    <section class="dsf-search__cta">
      <p>Para acessar o conteúdo completo do Dicionário (DSF) e visualizar todas as informações técnicas das substâncias, crie sua conta gratuita ou entre com seu login.</p>
      <p><strong>Leva menos de 1 minuto.</strong></p>
      <p>
        <a class="button button-primary" href="<?php echo esc_url( home_url( '/v2/cadastro/' ) ); ?>">
          CRIAR CONTA GRATUITAMENTE
        </a>
      </p>
    </section>

  </div>
</main>

<?php
get_footer();