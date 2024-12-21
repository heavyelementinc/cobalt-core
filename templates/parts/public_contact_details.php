<?php
    $name = __APP_SETTINGS__['PublicContact_name'];
    $phone = __APP_SETTINGS__['PublicContact_phone'];
    $fax = __APP_SETTINGS__['PublicContact_fax'];
    $email = __APP_SETTINGS__['PublicContact_email'];
    $addr1 = __APP_SETTINGS__['PublicContact_street_address1'];
    $addr2 = __APP_SETTINGS__['PublicContact_street_address2'];
    $city  = __APP_SETTINGS__['PublicContent_city'];
    $state = __APP_SETTINGS__['PublicContact_state'];
    $zipcode = __APP_SETTINGS__['PublicContact_zip'];
    $address_link = __APP_SETTINGS__['PublicContact_address_link'];
    $address = "";
    if($addr1) $address .= "$addr1<br>";
    if($addr2) $address .= "$addr2<br>";
    if($city) $address .= "$city, ";
    if($state) $address .= "$state ";
    if($zipcode) $address .= "$zipcode";
    if($address_link && $address) $address = "<a href=\"$address_link\">$address</a>";

    $social_links = social_media_links();
?>
<div class="template-callout {{classes}}">
    <h1><?= $this->vars['details_title'] ?? "Contact Us" ?></h1>
    <ul class="list-panel contact-panel">
        <?= ($name) ? "<li><h2>Contact</h2><i name='person'></i> $name</li>" : "" ?>
        <?= ($phone) ? "<li><h2>Phone</h2><a href='tel:$phone'><i name='phone'></i> ".phone_number_format($phone)."</a></li>" : "" ?>
        <?= ($fax) ? "<li><h2>Fax</h2><a href='tel:$fax'><i name='fax'></i> ".phone_number_format($fax)."</a></li>" : "" ?>
        <?= ($email) ? "<li><h2>Email</h2><a href='mailto:$email'><i name='email'></i> ".$email."</a></li>" : "" ?>
        <?= ($address) ? "<li><h2>Address</h2><address>$address</address></li>" : ""; ?>
        <?= ($social_links) ? "<li><h2>Social Media</h2>$social_links</li>" : ""; ?>
    </ul>
    <img src="" alt="">
</div>