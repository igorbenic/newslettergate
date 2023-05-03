<div class="newslettergate">
    <div class="newslettergate-loader"></div>
    <?php

    if ( ! empty( $heading ) ) {
        ?><h3><?php echo esc_html( $heading ); ?></h3><?php
    }

    if ( ! empty( $text ) ) {
        ?><p><?php echo esc_html( $text ); ?></p><?php
    }

    if ( ! empty( $errors ) ) {
        ?><div class="newslettergate-error"><p><?php echo wp_kses_post( implode( '</p><p>', $errors ) ); ?></></div><?php
    }
    ?>
    <form class="newslettergate-form" method="POST">
        <input type="hidden" name="ng_action" value="newslettergate_subscribe" />
        <input type="hidden" name="ng_integration" value="<?php echo esc_attr( $integration ); ?>" />
        <input type="hidden" name="ng_list" value="<?php echo esc_attr( $list_id ); ?>" />

        <input type="email" name="ng_email" value="<?php echo esc_attr( $email ); ?>" placeholder="<?php esc_attr_e( 'Enter Email to subscribe', 'newslettergate' ); ?>" />
        <button type="submit" class="newslettergate-button"><?php echo esc_html( $button ); ?></button>
    </form>
</div>