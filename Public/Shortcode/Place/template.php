<?php
?>


      <div id="FloorMapPlace">

          <span class="fmBack"><a href="<?php echo $BackLink; ?>"><?php echo esc_html__('Place.BackLink', 'mFloorMap'); ?></a></span>

        <h1><?php echo esc_html($Place['Title']); ?></h1>

        <h2><?php echo esc_html($Place['OfficialTitle']); ?></h2>

        <div class="fmLoc">
            <?php echo esc_html__('Place.Loc', 'mFloorMap'); ?> <?php echo esc_html($Place['LocationMark']); ?>
            <br />
            <?php echo esc_html__('Place.Floor', 'mFloorMap'); ?> <?php echo esc_html($Place['FloTitle']); ?>

        </div>

        <div class="fmLogoBlock">

            <img src="<?php echo $LogoSrc; ?>" class="fmLogo" alt="<?php echo esc_attr($Place['Title']); ?>" />

            <div class="fmTel"><?php echo esc_html($Place['ContactInfo']); ?></div>
            <div class="fmTime"><?php echo esc_html($Place['TimingInfo']); ?></div>
            <div class="fmTags"><ul><?php 
                    if (!empty($Tags)) {echo '<li>'.implode('</li><li>', array_map('esc_html',$Tags)).'</li>';}
                ?></ul></div>

        </div>

        <div class="fmDesc"><?php echo nl2br(esc_html($Place['Descr'])); ?></div>


        <?php if ($PhotoSrc) {echo '<img src="'.$PhotoSrc.'" class="fmPhoto" alt="'.esc_attr($Place['Title']).'" />';} 
        ?>

      </div>

