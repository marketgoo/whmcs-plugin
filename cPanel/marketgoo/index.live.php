<?php
require("marketgoo.live.php");

$mktgoo = new Mktgoo();

$buy_url = $mktgoo->config['buy_url'];

// redirect to the buy URL if configured
if (isset($buy_url) && !empty($buy_url)) {
    header('Location: ' . $plan['buyUrl']);
    exit();
}

/*
$plan = $mktgoo->get_active_plan_for_main();
//var_dump($plan);

if ($plan['status'] == 1)
{
    header('Location: ' . $plan['loginUrl']);
    exit();
}
else
{
    header('Location: ' . $plan['buyUrl']);
    exit();
}
 */

$marketgooPlans = $mktgoo->get_buy_plans();
//echo '<pre>';
//var_dump($marketgooPlans);
//echo '</pre>';
//die;

define("DS", DIRECTORY_SEPARATOR);
define("marketgoo_INCLUDES", '/usr/local/cpanel/share/marketgoo/');
require_once marketgoo_INCLUDES.'marketgooLoader.php';

$CPANEL = $mktgoo->cpanel;
$marketgoo = new MarketgooMainController('CPanel', __DIR__);

echo MarketgooDriver::localAPI()->getHeader();
?>
<style type="text/css">

    body {
        line-height: 23px !important;
        font-family: "Open sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
    }

    form {
        max-width: none;
    }

    #mktgooForm {
        margin-top: 30px;
    }

    .marketgoo-form {
        padding: 20px;
        margin-top: 30px;
    }

    .logo{
        height: 62px;
        margin: -30px 0px 5px 0px;
    }

    .marketgoo-form p {
        font-size: 14px;
        color: #7f7f7f;
        line-height: 20px;
    }

    .config-group {
        background: #eee;
        padding: 20px 20px;
        position: relative;
        border-radius: 5px;
    }

    .config-group .errors {
        font-size: 14px;
        font-weight: bold;
        margin-bottom: 10px;
        padding: 3px 0px;
        text-align: center;
        background: #ff8c8b;
        display: none;
    }

    .config-group label {
        font-size: 16px;
        font-weight: normal;
        line-height: 30px;
        width: 22%;
        color: #666;
        text-align: right;
        padding: 5px 11px 0px 0px;
        display: inline-block;
        vertical-align: middle;
    }

    .config-group label small {
        display: block;
        font-weight: normal;
    }

    .config-group label.double-line{
        line-height: 16px;
    }

    .config-group input {
        font-size: 14px;
        padding: 2px;
        width: 350px;
    }


    .controls{
        display: inline-block;
        vertical-align: middle;
    }

    .control-group {
        text-align: center;
    }

    .marketgoo-form input{
        border: solid 1px #bbb;
        border-radius: 2px;
        -webkit-border-radius: 2px;
        -moz-border-radius: 2px;
        outline: none!important;
        background: #fff;
        font-size: 16px;
        padding: 8px 8px 6px;
        margin-bottom: 5px;
    }

    .btn {
        border-radius: 9px;
        -webkit-border-radius: 9px;
        -moz-border-radius: 9px;
    }

    .marketgoo-form .btn-orange{
        background: #ff5700;
        color: white;
        border: none;
    }

    .marketgoo-form .btn-orange:hover{
        cursor: pointer;
        background-color: #cc4600;
    }

    .marketgoo-form .btn-grey{
        background: #808080;
        color: white;
        border: none;
    }

    .marketgoo-form .btn-grey:hover{
        cursor: default;
    }

    .marketgoo-form .form-actions {
        margin: 20px 0px 0px 0px;
        text-align: center;
    }

    .marketgoo-form p.disclaimer {
        margin: 0px;
        font-size: 11px;
    }

    .marketgoo-form p.disclaimer a{
        color: #ff5700;
    }

    .marketgoo-form h2{
        color: #515151;
        margin: 20px 0px;
        font-size: 24px;
        font-weight: bold;
        text-align: left;
        line-height: 1.4em;
    }

    .marketgoo-form .blue-header{
        color: white;
        background: #3fb6fd;
        padding: 15px 20px;
        margin: 0px 0px 30px 0px;
        font-size: 16px;
        font-weight: normal;
        position: absolute;
        left: 0;
        right: 0;
        top: 29px;
        text-align: center;
    }

    .testimonial{
        margin-bottom: 60px;
        text-align: center;
    }

    .testimonial div{
        display: inline-block;
        vertical-align: middle;
    }

    .testimonial-text{
        width: 62%;
        margin-right: 2%;
    }

    .testimonial-image{
        width: 35%;
        margin-top: 30px;
    }

    .testimonial-text h3{
        background: none;
        font-weight: normal;
        color: #515151;
        font-size: 24px;
        text-align: left;
    }

    .testimonial-text p{
        text-align: left;
        font-style: italic;
        line-height: 24px;
    }

    .testimonial-text p.signature{
        color: #515151;
        font-weight: bold;
        font-style: normal;
        line-height: 18px;
    }

    .testimonial-text p.signature a{
        color: #3fb6fd;
        font-weight: normal;
        font-size: 13px;
    }

    .screenshot{
        text-align: center;
        margin: 80px 0px 35px 0px;
    }

    .screenshot img{
        -webkit-box-shadow: 0px 0px 50px rgba(0,0,0,0.4);
        -moz-box-shadow: 0px 0px 50px rgba(0,0,0,0.4);
        box-shadow: 0px 0px 50px rgba(0,0,0,0.4);
        -webkit-border-radius: 6px;
        -moz-border-radius: 6px;
        border-radius: 6px;
    }

    .preview-title{
        background: none;
        font-weight: normal;
        color: #515151;
        font-size: 23px;
        text-align: left;
        margin-bottom: 5px;
    }

    .diagrams{
        position: relative;
        text-align: center;
        padding: 30px 0px 50px;
    }

    .diagrams img {
        -webkit-box-shadow: 0px 0px 50px rgba(0,0,0,0.4);
        -moz-box-shadow: 0px 0px 50px rgba(0,0,0,0.4);
        box-shadow: 0px 0px 50px rgba(0,0,0,0.4);
        -webkit-border-radius: 6px;
        -moz-border-radius: 6px;
        border-radius: 6px;
        max-width: 100%;
        height: auto;
    }

    .diagrams ul.pins{
        list-style: none outside;
    }

    .diagrams .pins li{
        width: 190px;
        position: absolute;
        text-shadow: -1px 0 white, 0 1px white, 1px 0 white, 0 -1px white;
    }

    .diagrams .pins li h3{
        background: none;
        color: #515151;
        font-size: 15px !important;
    }

    .diagrams .pins .pin-evolucion {
        top: 130px;
        left: 50%;
        padding-right: 10px;
        border-top: 1px solid #bbb;
        text-align: left;
        margin-left: -483px;
    }

    .diagrams .pins .pin-interfaz {
        top: 47px;
        right: 50%;
        text-align: right;
        border-top: 1px solid #bbb;
        padding-left: 10px;
        margin-right: -465px;
    }

    .diagrams .pins .pin-tareas {
        top: 294px;
        right: 50%;
        text-align: right;
        border-top: 1px solid #bbb;
        padding-left: 10px;
        margin-right: -472px;
    }

    .to-top{
        text-align: center;
        margin: 45px 0px;
    }

    a.btn {
        text-decoration: none;
    }

    .domains-list th, .domains-list td {
        text-align: center;
        color: #515151;
        border-color: #515151!important;
    }

    .control {
        border-radius: 50%;
        display: inline-block;
        height: 12px;
        width: 12px;
    }

    .control.control-off {
        background: rgb(230, 0, 0) none repeat scroll 0 0;
    }

    .control.control-on {
        background: #5cb85c;
    }

</style>

<div class="marketgoo-form">
    <img src="logo.png" class="logo" />

    <h2><?php echo $mktgoo->translate("Get started today with our 6 SEO tools (free for life!) and we'll unlock the full potential of marketgoo with a free 10 day trial!"); ?></h2>
    <p><?php echo $mktgoo->translate("With our tools you will be able to <strong>submit your site</strong> to Google, <strong>improve your SEO</strong>, and enhance your overall <strong>online marketing strategy</strong>. Start increasing your revenue by receiving more <strong>qualified leads</strong> with marketgoo!"); ?></p>

    <div class="row">
        <div class="col-lg-12">
            <div class="config-group">
                <table class="table table-striped domains-list">
                    <thead>
                        <tr>
                            <th><?php echo $mktgoo->translate('Status') ?></th>
                            <th><?php echo $mktgoo->translate('Domain') ?></th>
                            <th><?php echo $mktgoo->translate('Plan') ?></th>
                            <th><?php echo $mktgoo->translate('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($mktgoo->domains as $domain): ?>
                            <tr>
                                <td><span class="control control-<?php echo ($domain['status'] == 1) ? 'on' : 'off' ?>"></span></td>
                                <td><?php echo $domain['domainName'] ?></td>
                                <td>
                                    <?php if ($domain['status'] != 1): ?>
                                        <?php if (!empty($marketgooPlans)): ?>
                                            <select name="plan">
                                                <?php foreach ($marketgooPlans as $marketgooPlan): ?>
                                                    <option value="<?php echo $marketgooPlan['pid'] ?>"><?php echo $marketgooPlan['name'] ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        <?php else: ?>
                                            <select name="plan">
                                                <option>None</option>
                                            </select>
                                        <?php endif ?>
                                    <?php else: ?>
                                        <?php echo $domain['plan'] ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($domain['status'] == 1): ?>
                                        <a href="<?php echo $domain['loginUrl'] ?>" target="_blank" class="btn btn-success">
                                            <?php echo $mktgoo->translate('Login to marketgoo') ?>
                                        </a>
                                    <?php else: ?>
                                        <?php if (!empty($marketgooPlans)): ?>
                                            <a href="<?php echo $domain['buyUrl'].'&pid='.$marketgooPlans[0]['pid'] ?>" target="_blank" class="btn btn-orange buy_button">
                                                <?php echo $mktgoo->translate('Buy Now') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="btn btn-grey buy_button">
                                                <?php echo $mktgoo->translate('Buy Now') ?>
                                            </span>
                                        <?php endif ?>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="screenshot">
        <h3 class="preview-title"><?php echo $mktgoo->translate("marketgoo is EASY to use"); ?></h3>
        <div class="diagrams">

            <img src="<?php echo $mktgoo->translate("screenshot_en.jpg"); ?>" width="600" height="429">
            <ul class="pins">
                <li class="pin-evolucion">
                    <h3><?php echo $mktgoo->translate("Track your progress"); ?></h3>
                    <p><?php echo $mktgoo->translate("We prioritize your actions so you can measure your progress."); ?></p>
                </li>
                <li class="pin-interfaz">
                    <h3><?php echo $mktgoo->translate("Easy to use interface"); ?></h3>
                    <p><?php echo $mktgoo->translate("Three simple work areas, built to help you."); ?></p>
                </li>
                <li class="pin-tareas">
                    <h3><?php echo $mktgoo->translate("Customized tasks"); ?></h3>
                    <p><?php echo $mktgoo->translate("We analyze your web site daily to show you a customized task list."); ?></p>
                </li>
            </ul>

        </div>
    </div>

    <div class="testimonial">
        <div class="testimonial-text">
            <h3><?php echo $mktgoo->translate("Our customers feedback"); ?></h3>
            <p>
                "<?php echo $mktgoo->translate("After using marketgoo for some weeks, results are thriving. I've managed to beat my competitors in rankings that are of importance to me. The visit results I see on Google Analytics are growing and now I better understand how to improve my results. marketgoo is an addictive app and easy to use. You do not need any technical knowledge but mostly it allows you to control the reach of your company’s branding, and there is none better suited for this task than yourself."); ?>"
            </p>
            <p class="signature">
                - Jose Sampayo <br/>
                <a href="http://www.endoscopiaveterinaria.es" target="_blank">(www.endoscopiaveterinaria.es)</a>
            </p>

        </div>

        <div class="testimonial-image">
            <img src="testimonial.jpg" alt="Jose Sampayo" />
        </div>
    </div>

    <div class="to-top">
        <a href="<?= $redirectTo ?>" target="_blank" class="btn"><?php echo $mktgoo->translate("Let's go!"); ?> »</a>
    </div>

    <p class="mktgoo-footer">
        <a href="http://www.marketgoo.com/" target="_blank"><?php echo $mktgoo->translate("marketgoo home"); ?> &raquo;</a> |
        <a href="http://www.marketgoo.com/easy-seo-tool/what-is-marketgoo" target="_blank"><?php echo $mktgoo->translate("Learn more about marketgoo"); ?> &raquo;</a>
    </p>

</div>

<script>
    var planSelect = document.querySelector('select[name=plan]');
    planSelect.addEventListener('change', function ()
    {
        var planBuyButton = planSelect.parentNode.parentNode.querySelector('a.buy_button');
        var href = planBuyButton.getAttribute('href');
        
        if(href.indexOf("pid") != -1){
            href = href.substring(0, href.indexOf("pid")-1);
        }
        var newHref = href+"&pid="+planSelect.value;
        planBuyButton.setAttribute("href", newHref);
    });

    
//    $(document).ready(function () {
//        $("input[name=plan]").change(function () {
//           console.log($(this).val());
//        });
//    });
</script>

<?php echo MarketgooDriver::localAPI()->getFooter(); ?>
