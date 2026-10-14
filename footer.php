<?php
$phone_credit = is_page_template( 'page-home.php' ) ? carbon_get_the_post_meta( 'phone_model_credit' ) : '';
if ( $phone_credit ) : ?>
<p class="site-credit"><?php echo esc_html( $phone_credit ); ?></p>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
