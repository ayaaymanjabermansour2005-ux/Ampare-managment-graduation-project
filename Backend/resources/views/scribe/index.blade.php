<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Laravel API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../docs/css/theme-default.style.css" media="screen">
    <link rel="stylesheet" href="../docs/css/theme-default.print.css" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="../docs/js/tryitout-5.11.0.js"></script>

    <script src="../docs/js/theme-default-5.11.0.js"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="../docs/images/navbar.png" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-subscriber-meters">
                                <a href="#endpoints-GETapi-v1-subscriber-meters">GET api/v1/subscriber-meters</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-subscriber-meters--subscriber_meter_id-">
                                <a href="#endpoints-GETapi-v1-subscriber-meters--subscriber_meter_id-">GET api/v1/subscriber-meters/{subscriber_meter_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-subscriber-meters">
                                <a href="#endpoints-POSTapi-v1-subscriber-meters">POST api/v1/subscriber-meters</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-subscriber-meters--subscriber_meter_id-">
                                <a href="#endpoints-DELETEapi-v1-subscriber-meters--subscriber_meter_id-">DELETE api/v1/subscriber-meters/{subscriber_meter_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-subscriber-meters--subscriber_meter_id--qr">
                                <a href="#endpoints-GETapi-v1-subscriber-meters--subscriber_meter_id--qr">GET api/v1/subscriber-meters/{subscriber_meter_id}/qr</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-invoices--invoice_id--verify">
                                <a href="#endpoints-GETapi-v1-invoices--invoice_id--verify">GET api/v1/invoices/{invoice_id}/verify</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-adar-almstkhdmyn-osgl-altdkyk" class="tocify-header">
                <li class="tocify-item level-1" data-unique="adar-almstkhdmyn-osgl-altdkyk">
                    <a href="#adar-almstkhdmyn-osgl-altdkyk">إدارة المستخدمين وسجل التدقيق</a>
                </li>
                                    <ul id="tocify-subheader-adar-almstkhdmyn-osgl-altdkyk" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users">
                                <a href="#adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users">GET api/v1/users</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users-locked">
                                <a href="#adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users-locked">GET api/v1/users/locked</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users--user_id-">
                                <a href="#adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users--user_id-">GET api/v1/users/{user_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almstkhdmyn-osgl-altdkyk-PATCHapi-v1-users--user_id-">
                                <a href="#adar-almstkhdmyn-osgl-altdkyk-PATCHapi-v1-users--user_id-">PATCH api/v1/users/{user_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almstkhdmyn-osgl-altdkyk-DELETEapi-v1-users--user_id-">
                                <a href="#adar-almstkhdmyn-osgl-altdkyk-DELETEapi-v1-users--user_id-">DELETE api/v1/users/{user_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almstkhdmyn-osgl-altdkyk-PATCHapi-v1-users--user_id--unlock">
                                <a href="#adar-almstkhdmyn-osgl-altdkyk-PATCHapi-v1-users--user_id--unlock">PATCH api/v1/users/{user_id}/unlock</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-activity-logs">
                                <a href="#adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-activity-logs">GET api/v1/activity-logs</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-adar-almoldat" class="tocify-header">
                <li class="tocify-item level-1" data-unique="adar-almoldat">
                    <a href="#adar-almoldat">إدارة المولدات</a>
                </li>
                                    <ul id="tocify-subheader-adar-almoldat" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="adar-almoldat-GETapi-v1-generators-available">
                                <a href="#adar-almoldat-GETapi-v1-generators-available">GET api/v1/generators/available</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almoldat-GETapi-v1-generators">
                                <a href="#adar-almoldat-GETapi-v1-generators">GET api/v1/generators</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almoldat-POSTapi-v1-generators">
                                <a href="#adar-almoldat-POSTapi-v1-generators">POST api/v1/generators</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almoldat-GETapi-v1-generators--generator_id-">
                                <a href="#adar-almoldat-GETapi-v1-generators--generator_id-">GET api/v1/generators/{generator_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almoldat-PATCHapi-v1-generators--generator_id-">
                                <a href="#adar-almoldat-PATCHapi-v1-generators--generator_id-">PATCH api/v1/generators/{generator_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almoldat-DELETEapi-v1-generators--generator_id-">
                                <a href="#adar-almoldat-DELETEapi-v1-generators--generator_id-">DELETE api/v1/generators/{generator_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almoldat-GETapi-v1-generators--generator_id--qr">
                                <a href="#adar-almoldat-GETapi-v1-generators--generator_id--qr">GET api/v1/generators/{generator_id}/qr</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almoldat-GETapi-v1-generators--generator_id--attachments">
                                <a href="#adar-almoldat-GETapi-v1-generators--generator_id--attachments">GET api/v1/generators/{generator_id}/attachments</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="adar-almoldat-POSTapi-v1-generators--generator_id--attachments">
                                <a href="#adar-almoldat-POSTapi-v1-generators--generator_id--attachments">POST api/v1/generators/{generator_id}/attachments</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-alaaatal-oaltokaaat-althky" class="tocify-header">
                <li class="tocify-item level-1" data-unique="alaaatal-oaltokaaat-althky">
                    <a href="#alaaatal-oaltokaaat-althky">الأعطال والتوقعات الذكية</a>
                </li>
                                    <ul id="tocify-subheader-alaaatal-oaltokaaat-althky" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-GETapi-v1-faults">
                                <a href="#alaaatal-oaltokaaat-althky-GETapi-v1-faults">GET api/v1/faults</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-POSTapi-v1-faults">
                                <a href="#alaaatal-oaltokaaat-althky-POSTapi-v1-faults">POST api/v1/faults</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-GETapi-v1-faults--fault_id-">
                                <a href="#alaaatal-oaltokaaat-althky-GETapi-v1-faults--fault_id-">GET api/v1/faults/{fault_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--verify">
                                <a href="#alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--verify">PATCH api/v1/faults/{fault_id}/verify</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--decide-repair">
                                <a href="#alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--decide-repair">PATCH api/v1/faults/{fault_id}/decide-repair</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--override-status">
                                <a href="#alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--override-status">PATCH api/v1/faults/{fault_id}/override-status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-DELETEapi-v1-faults--fault_id-">
                                <a href="#alaaatal-oaltokaaat-althky-DELETEapi-v1-faults--fault_id-">DELETE api/v1/faults/{fault_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-GETapi-v1-fault-predictions">
                                <a href="#alaaatal-oaltokaaat-althky-GETapi-v1-fault-predictions">GET api/v1/fault-predictions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-GETapi-v1-fault-predictions--fault_prediction_id-">
                                <a href="#alaaatal-oaltokaaat-althky-GETapi-v1-fault-predictions--fault_prediction_id-">GET api/v1/fault-predictions/{fault_prediction_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-POSTapi-v1-fault-predictions">
                                <a href="#alaaatal-oaltokaaat-althky-POSTapi-v1-fault-predictions">POST api/v1/fault-predictions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm">
                                <a href="#alaaatal-oaltokaaat-althky-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm">PATCH api/v1/fault-predictions/{fault_prediction_id}/confirm</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaaatal-oaltokaaat-althky-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss">
                                <a href="#alaaatal-oaltokaaat-althky-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss">PATCH api/v1/fault-predictions/{fault_prediction_id}/dismiss</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-alashaaarat" class="tocify-header">
                <li class="tocify-item level-1" data-unique="alashaaarat">
                    <a href="#alashaaarat">الإشعارات</a>
                </li>
                                    <ul id="tocify-subheader-alashaaarat" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="alashaaarat-GETapi-v1-notifications">
                                <a href="#alashaaarat-GETapi-v1-notifications">GET api/v1/notifications</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashaaarat-PATCHapi-v1-notifications-read-all">
                                <a href="#alashaaarat-PATCHapi-v1-notifications-read-all">PATCH api/v1/notifications/read-all</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashaaarat-PATCHapi-v1-notifications--notification--read">
                                <a href="#alashaaarat-PATCHapi-v1-notifications--notification--read">PATCH api/v1/notifications/{notification}/read</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashaaarat-DELETEapi-v1-notifications--notification-">
                                <a href="#alashaaarat-DELETEapi-v1-notifications--notification-">DELETE api/v1/notifications/{notification}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-alashtrakat-otlbat-alkhdm" class="tocify-header">
                <li class="tocify-item level-1" data-unique="alashtrakat-otlbat-alkhdm">
                    <a href="#alashtrakat-otlbat-alkhdm">الاشتراكات وطلبات الخدمة</a>
                </li>
                                    <ul id="tocify-subheader-alashtrakat-otlbat-alkhdm" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions">
                                <a href="#alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions">GET api/v1/subscriptions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions--subscription_id-">
                                <a href="#alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions--subscription_id-">GET api/v1/subscriptions/{subscription_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-POSTapi-v1-subscriptions">
                                <a href="#alashtrakat-otlbat-alkhdm-POSTapi-v1-subscriptions">POST api/v1/subscriptions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscriptions--subscription_id--status">
                                <a href="#alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscriptions--subscription_id--status">PATCH api/v1/subscriptions/{subscription_id}/status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions--subscription_id--contract-pdf">
                                <a href="#alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions--subscription_id--contract-pdf">GET api/v1/subscriptions/{subscription_id}/contract-pdf</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-GETapi-v1-subscription-service-requests">
                                <a href="#alashtrakat-otlbat-alkhdm-GETapi-v1-subscription-service-requests">GET api/v1/subscription-service-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-GETapi-v1-subscription-service-requests--serviceRequest_id-">
                                <a href="#alashtrakat-otlbat-alkhdm-GETapi-v1-subscription-service-requests--serviceRequest_id-">GET api/v1/subscription-service-requests/{serviceRequest_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-POSTapi-v1-subscription-service-requests">
                                <a href="#alashtrakat-otlbat-alkhdm-POSTapi-v1-subscription-service-requests">POST api/v1/subscription-service-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review">
                                <a href="#alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review">PATCH api/v1/subscription-service-requests/{serviceRequest_id}/review</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel">
                                <a href="#alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel">PATCH api/v1/subscription-service-requests/{serviceRequest_id}/cancel</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-aldfaaat-oosayl-aldfaa" class="tocify-header">
                <li class="tocify-item level-1" data-unique="aldfaaat-oosayl-aldfaa">
                    <a href="#aldfaaat-oosayl-aldfaa">الدفعات ووسائل الدفع</a>
                </li>
                                    <ul id="tocify-subheader-aldfaaat-oosayl-aldfaa" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-GETapi-v1-payments">
                                <a href="#aldfaaat-oosayl-aldfaa-GETapi-v1-payments">GET api/v1/payments</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-GETapi-v1-payments-export">
                                <a href="#aldfaaat-oosayl-aldfaa-GETapi-v1-payments-export">GET api/v1/payments/export</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-GETapi-v1-payments--payment_id-">
                                <a href="#aldfaaat-oosayl-aldfaa-GETapi-v1-payments--payment_id-">GET api/v1/payments/{payment_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-POSTapi-v1-payments">
                                <a href="#aldfaaat-oosayl-aldfaa-POSTapi-v1-payments">POST api/v1/payments</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--approve">
                                <a href="#aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--approve">اعتماد دفعة Pending/NeedsCorrection → Paid.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--reject">
                                <a href="#aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--reject">PATCH api/v1/payments/{payment_id}/reject</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--needs-correction">
                                <a href="#aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--needs-correction">PATCH api/v1/payments/{payment_id}/needs-correction</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--resubmit">
                                <a href="#aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--resubmit">PATCH api/v1/payments/{payment_id}/resubmit</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--cancel">
                                <a href="#aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--cancel">PATCH api/v1/payments/{payment_id}/cancel</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-GETapi-v1-payment-methods">
                                <a href="#aldfaaat-oosayl-aldfaa-GETapi-v1-payment-methods">GET api/v1/payment-methods</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-POSTapi-v1-payment-methods">
                                <a href="#aldfaaat-oosayl-aldfaa-POSTapi-v1-payment-methods">POST api/v1/payment-methods</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-PUTapi-v1-payment-methods--payment_method_id-">
                                <a href="#aldfaaat-oosayl-aldfaa-PUTapi-v1-payment-methods--payment_method_id-">PUT api/v1/payment-methods/{payment_method_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aldfaaat-oosayl-aldfaa-DELETEapi-v1-payment-methods--payment_method_id-">
                                <a href="#aldfaaat-oosayl-aldfaa-DELETEapi-v1-payment-methods--payment_method_id-">DELETE api/v1/payment-methods/{payment_method_id}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-alshkao" class="tocify-header">
                <li class="tocify-item level-1" data-unique="alshkao">
                    <a href="#alshkao">الشكاوى</a>
                </li>
                                    <ul id="tocify-subheader-alshkao" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="alshkao-GETapi-v1-complaints">
                                <a href="#alshkao-GETapi-v1-complaints">GET api/v1/complaints</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alshkao-POSTapi-v1-complaints">
                                <a href="#alshkao-POSTapi-v1-complaints">POST api/v1/complaints</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alshkao-GETapi-v1-complaints--id-">
                                <a href="#alshkao-GETapi-v1-complaints--id-">GET api/v1/complaints/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alshkao-PATCHapi-v1-complaints--complaint_id--status">
                                <a href="#alshkao-PATCHapi-v1-complaints--complaint_id--status">PATCH api/v1/complaints/{complaint_id}/status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alshkao-DELETEapi-v1-complaints--id-">
                                <a href="#alshkao-DELETEapi-v1-complaints--id-">DELETE api/v1/complaints/{id}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-alaarod-oalkhsomat" class="tocify-header">
                <li class="tocify-item level-1" data-unique="alaarod-oalkhsomat">
                    <a href="#alaarod-oalkhsomat">العروض والخصومات</a>
                </li>
                                    <ul id="tocify-subheader-alaarod-oalkhsomat" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="alaarod-oalkhsomat-GETapi-v1-offers">
                                <a href="#alaarod-oalkhsomat-GETapi-v1-offers">GET api/v1/offers</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaarod-oalkhsomat-POSTapi-v1-offers">
                                <a href="#alaarod-oalkhsomat-POSTapi-v1-offers">POST api/v1/offers</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaarod-oalkhsomat-GETapi-v1-offers--id-">
                                <a href="#alaarod-oalkhsomat-GETapi-v1-offers--id-">GET api/v1/offers/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaarod-oalkhsomat-PATCHapi-v1-offers--id-">
                                <a href="#alaarod-oalkhsomat-PATCHapi-v1-offers--id-">PATCH api/v1/offers/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaarod-oalkhsomat-PATCHapi-v1-offers--offer_id--cancel">
                                <a href="#alaarod-oalkhsomat-PATCHapi-v1-offers--offer_id--cancel">PATCH api/v1/offers/{offer_id}/cancel</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alaarod-oalkhsomat-DELETEapi-v1-offers--id-">
                                <a href="#alaarod-oalkhsomat-DELETEapi-v1-offers--id-">DELETE api/v1/offers/{id}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-alfnyyn-oaoamr-alshghl" class="tocify-header">
                <li class="tocify-item level-1" data-unique="alfnyyn-oaoamr-alshghl">
                    <a href="#alfnyyn-oaoamr-alshghl">الفنيين وأوامر الشغل</a>
                </li>
                                    <ul id="tocify-subheader-alfnyyn-oaoamr-alshghl" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-GETapi-v1-technicians">
                                <a href="#alfnyyn-oaoamr-alshghl-GETapi-v1-technicians">GET api/v1/technicians</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-GETapi-v1-technicians--technician_id-">
                                <a href="#alfnyyn-oaoamr-alshghl-GETapi-v1-technicians--technician_id-">GET api/v1/technicians/{technician_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-POSTapi-v1-technicians">
                                <a href="#alfnyyn-oaoamr-alshghl-POSTapi-v1-technicians">POST api/v1/technicians</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technicians--technician_id-">
                                <a href="#alfnyyn-oaoamr-alshghl-PATCHapi-v1-technicians--technician_id-">PATCH api/v1/technicians/{technician_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-DELETEapi-v1-technicians--technician_id-">
                                <a href="#alfnyyn-oaoamr-alshghl-DELETEapi-v1-technicians--technician_id-">DELETE api/v1/technicians/{technician_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-GETapi-v1-technicians--technician_id--attachments">
                                <a href="#alfnyyn-oaoamr-alshghl-GETapi-v1-technicians--technician_id--attachments">GET api/v1/technicians/{technician_id}/attachments</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-POSTapi-v1-technicians--technician_id--attachments">
                                <a href="#alfnyyn-oaoamr-alshghl-POSTapi-v1-technicians--technician_id--attachments">POST api/v1/technicians/{technician_id}/attachments</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-GETapi-v1-generators--generator_id--available-technicians">
                                <a href="#alfnyyn-oaoamr-alshghl-GETapi-v1-generators--generator_id--available-technicians">GET api/v1/generators/{generator_id}/available-technicians</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-GETapi-v1-technician-tasks">
                                <a href="#alfnyyn-oaoamr-alshghl-GETapi-v1-technician-tasks">GET api/v1/technician-tasks</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-GETapi-v1-technician-tasks--technician_task_id-">
                                <a href="#alfnyyn-oaoamr-alshghl-GETapi-v1-technician-tasks--technician_task_id-">GET api/v1/technician-tasks/{technician_task_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-POSTapi-v1-technician-tasks">
                                <a href="#alfnyyn-oaoamr-alshghl-POSTapi-v1-technician-tasks">POST api/v1/technician-tasks</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--assign">
                                <a href="#alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--assign">PATCH api/v1/technician-tasks/{technician_task_id}/assign</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way">
                                <a href="#alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way">PATCH api/v1/technician-tasks/{technician_task_id}/on-the-way</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--start">
                                <a href="#alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--start">PATCH api/v1/technician-tasks/{technician_task_id}/start</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts">
                                <a href="#alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts">PATCH api/v1/technician-tasks/{technician_task_id}/waiting-parts</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--submit">
                                <a href="#alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--submit">PATCH api/v1/technician-tasks/{technician_task_id}/submit</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--review">
                                <a href="#alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--review">PATCH api/v1/technician-tasks/{technician_task_id}/review</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--cancel">
                                <a href="#alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--cancel">PATCH api/v1/technician-tasks/{technician_task_id}/cancel</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-alkraaaat-oalfoatyr" class="tocify-header">
                <li class="tocify-item level-1" data-unique="alkraaaat-oalfoatyr">
                    <a href="#alkraaaat-oalfoatyr">القراءات والفواتير</a>
                </li>
                                    <ul id="tocify-subheader-alkraaaat-oalfoatyr" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-GETapi-v1-meter-readings">
                                <a href="#alkraaaat-oalfoatyr-GETapi-v1-meter-readings">GET api/v1/meter-readings</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-GETapi-v1-meter-readings--meter_reading_id-">
                                <a href="#alkraaaat-oalfoatyr-GETapi-v1-meter-readings--meter_reading_id-">GET api/v1/meter-readings/{meter_reading_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-POSTapi-v1-meter-readings">
                                <a href="#alkraaaat-oalfoatyr-POSTapi-v1-meter-readings">POST api/v1/meter-readings</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-GETapi-v1-invoices">
                                <a href="#alkraaaat-oalfoatyr-GETapi-v1-invoices">GET api/v1/invoices</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-GETapi-v1-invoices-export">
                                <a href="#alkraaaat-oalfoatyr-GETapi-v1-invoices-export">GET api/v1/invoices/export</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--payment-methods">
                                <a href="#alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--payment-methods">GET api/v1/invoices/{invoice_id}/payment-methods</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-PATCHapi-v1-invoices--invoice_id--cancel">
                                <a href="#alkraaaat-oalfoatyr-PATCHapi-v1-invoices--invoice_id--cancel">PATCH api/v1/invoices/{invoice_id}/cancel</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--pdf">
                                <a href="#alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--pdf">GET api/v1/invoices/{invoice_id}/pdf</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--qr">
                                <a href="#alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--qr">GET api/v1/invoices/{invoice_id}/qr</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id-">
                                <a href="#alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id-">GET api/v1/invoices/{invoice_id}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-almhadthat" class="tocify-header">
                <li class="tocify-item level-1" data-unique="almhadthat">
                    <a href="#almhadthat">المحادثات</a>
                </li>
                                    <ul id="tocify-subheader-almhadthat" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="almhadthat-GETapi-v1-conversations">
                                <a href="#almhadthat-GETapi-v1-conversations">GET api/v1/conversations</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almhadthat-POSTapi-v1-conversations">
                                <a href="#almhadthat-POSTapi-v1-conversations">POST api/v1/conversations</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almhadthat-GETapi-v1-conversations--id-">
                                <a href="#almhadthat-GETapi-v1-conversations--id-">GET api/v1/conversations/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almhadthat-DELETEapi-v1-conversations--id-">
                                <a href="#almhadthat-DELETEapi-v1-conversations--id-">DELETE api/v1/conversations/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almhadthat-GETapi-v1-conversations--conversation_id--messages">
                                <a href="#almhadthat-GETapi-v1-conversations--conversation_id--messages">GET api/v1/conversations/{conversation_id}/messages</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almhadthat-POSTapi-v1-conversations--conversation_id--messages">
                                <a href="#almhadthat-POSTapi-v1-conversations--conversation_id--messages">POST api/v1/conversations/{conversation_id}/messages</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-almrfkat" class="tocify-header">
                <li class="tocify-item level-1" data-unique="almrfkat">
                    <a href="#almrfkat">المرفقات</a>
                </li>
                                    <ul id="tocify-subheader-almrfkat" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="almrfkat-GETapi-v1-attachments--id-">
                                <a href="#almrfkat-GETapi-v1-attachments--id-">GET api/v1/attachments/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almrfkat-GETapi-v1-attachments--attachment_id--download">
                                <a href="#almrfkat-GETapi-v1-attachments--attachment_id--download">GET api/v1/attachments/{attachment_id}/download</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almrfkat-DELETEapi-v1-attachments--id-">
                                <a href="#almrfkat-DELETEapi-v1-attachments--id-">DELETE api/v1/attachments/{id}</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-almsaaad-althky-oaltshkhys" class="tocify-header">
                <li class="tocify-item level-1" data-unique="almsaaad-althky-oaltshkhys">
                    <a href="#almsaaad-althky-oaltshkhys">المساعد الذكي والتشخيص</a>
                </li>
                                    <ul id="tocify-subheader-almsaaad-althky-oaltshkhys" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-GETapi-v1-generators--generator_id--diagnostics">
                                <a href="#almsaaad-althky-oaltshkhys-GETapi-v1-generators--generator_id--diagnostics">GET api/v1/generators/{generator_id}/diagnostics</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-POSTapi-v1-generators--generator_id--diagnostics">
                                <a href="#almsaaad-althky-oaltshkhys-POSTapi-v1-generators--generator_id--diagnostics">POST api/v1/generators/{generator_id}/diagnostics</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-POSTapi-v1-generator-diagnostics--reading_id--analyze">
                                <a href="#almsaaad-althky-oaltshkhys-POSTapi-v1-generator-diagnostics--reading_id--analyze">POST api/v1/generator-diagnostics/{reading_id}/analyze</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-available-generators">
                                <a href="#almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-available-generators">GET api/v1/ai-chat/available-generators</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-sessions">
                                <a href="#almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-sessions">GET api/v1/ai-chat/sessions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-sessions--aiChatSession_id-">
                                <a href="#almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-sessions--aiChatSession_id-">GET api/v1/ai-chat/sessions/{aiChatSession_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions">
                                <a href="#almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions">POST api/v1/ai-chat/sessions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages">
                                <a href="#almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages">POST api/v1/ai-chat/sessions/{aiChatSession_id}/messages</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction">
                                <a href="#almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction">POST api/v1/ai-chat/sessions/{aiChatSession_id}/submit-as-prediction</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-almshtrkyn-oalaadadat" class="tocify-header">
                <li class="tocify-item level-1" data-unique="almshtrkyn-oalaadadat">
                    <a href="#almshtrkyn-oalaadadat">المشتركين والعدادات</a>
                </li>
                                    <ul id="tocify-subheader-almshtrkyn-oalaadadat" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="almshtrkyn-oalaadadat-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type">
                                <a href="#almshtrkyn-oalaadadat-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type">PATCH api/v1/subscribers/{subscriber_id}/beneficiary-type</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-almsadk-oadar-alhsab" class="tocify-header">
                <li class="tocify-item level-1" data-unique="almsadk-oadar-alhsab">
                    <a href="#almsadk-oadar-alhsab">المصادقة وإدارة الحساب</a>
                </li>
                                    <ul id="tocify-subheader-almsadk-oadar-alhsab" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-POSTapi-v1-auth-register">
                                <a href="#almsadk-oadar-alhsab-POSTapi-v1-auth-register">POST api/v1/auth/register</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-POSTapi-v1-auth-login">
                                <a href="#almsadk-oadar-alhsab-POSTapi-v1-auth-login">POST api/v1/auth/login</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-POSTapi-v1-auth-forgot-password">
                                <a href="#almsadk-oadar-alhsab-POSTapi-v1-auth-forgot-password">POST api/v1/auth/forgot-password</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-POSTapi-v1-auth-reset-password">
                                <a href="#almsadk-oadar-alhsab-POSTapi-v1-auth-reset-password">POST api/v1/auth/reset-password</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-GETapi-v1-auth-verify-email--id---hash-">
                                <a href="#almsadk-oadar-alhsab-GETapi-v1-auth-verify-email--id---hash-">GET api/v1/auth/verify-email/{id}/{hash}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-POSTapi-v1-auth-email-resend">
                                <a href="#almsadk-oadar-alhsab-POSTapi-v1-auth-email-resend">POST api/v1/auth/email/resend</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-POSTapi-v1-auth-logout">
                                <a href="#almsadk-oadar-alhsab-POSTapi-v1-auth-logout">POST api/v1/auth/logout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-GETapi-v1-auth-me">
                                <a href="#almsadk-oadar-alhsab-GETapi-v1-auth-me">GET api/v1/auth/me</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-PATCHapi-v1-auth-password">
                                <a href="#almsadk-oadar-alhsab-PATCHapi-v1-auth-password">PATCH api/v1/auth/password</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="almsadk-oadar-alhsab-POSTapi-v1-auth-logout-other-devices">
                                <a href="#almsadk-oadar-alhsab-POSTapi-v1-auth-logout-other-devices">POST api/v1/auth/logout-other-devices</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-alokod" class="tocify-header">
                <li class="tocify-item level-1" data-unique="alokod">
                    <a href="#alokod">الوقود</a>
                </li>
                                    <ul id="tocify-subheader-alokod" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="alokod-GETapi-v1-generators--generator_id--fuel-purchases">
                                <a href="#alokod-GETapi-v1-generators--generator_id--fuel-purchases">GET api/v1/generators/{generator_id}/fuel/purchases</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alokod-POSTapi-v1-generators--generator_id--fuel-purchases">
                                <a href="#alokod-POSTapi-v1-generators--generator_id--fuel-purchases">POST api/v1/generators/{generator_id}/fuel/purchases</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alokod-GETapi-v1-generators--generator_id--fuel-readings">
                                <a href="#alokod-GETapi-v1-generators--generator_id--fuel-readings">GET api/v1/generators/{generator_id}/fuel/readings</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alokod-POSTapi-v1-generators--generator_id--fuel-readings">
                                <a href="#alokod-POSTapi-v1-generators--generator_id--fuel-readings">POST api/v1/generators/{generator_id}/fuel/readings</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alokod-GETapi-v1-generators--generator_id--fuel-consumption">
                                <a href="#alokod-GETapi-v1-generators--generator_id--fuel-consumption">GET api/v1/generators/{generator_id}/fuel/consumption</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alokod-GETapi-v1-generators--generator_id--fuel-cost-per-kwh">
                                <a href="#alokod-GETapi-v1-generators--generator_id--fuel-cost-per-kwh">GET api/v1/generators/{generator_id}/fuel/cost-per-kwh</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="alokod-GETapi-v1-generators--generator_id--fuel-status">
                                <a href="#alokod-GETapi-v1-generators--generator_id--fuel-status">GET api/v1/generators/{generator_id}/fuel/status</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-aamolat-almns" class="tocify-header">
                <li class="tocify-item level-1" data-unique="aamolat-almns">
                    <a href="#aamolat-almns">عمولات المنصة</a>
                </li>
                                    <ul id="tocify-subheader-aamolat-almns" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="aamolat-almns-GETapi-v1-platform-commissions">
                                <a href="#aamolat-almns-GETapi-v1-platform-commissions">GET api/v1/platform-commissions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aamolat-almns-GETapi-v1-platform-commissions-report-pdf">
                                <a href="#aamolat-almns-GETapi-v1-platform-commissions-report-pdf">GET api/v1/platform-commissions/report-pdf</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aamolat-almns-GETapi-v1-platform-commissions-export">
                                <a href="#aamolat-almns-GETapi-v1-platform-commissions-export">GET api/v1/platform-commissions/export</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="aamolat-almns-PATCHapi-v1-platform-commissions--platform_commission_id--status">
                                <a href="#aamolat-almns-PATCHapi-v1-platform-commissions--platform_commission_id--status">PATCH api/v1/platform-commissions/{platform_commission_id}/status</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="../docs/collection.json">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="../docs/openapi.yaml">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: July 21, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-v1-subscriber-meters">GET api/v1/subscriber-meters</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-subscriber-meters">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/subscriber-meters" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriber-meters"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-subscriber-meters">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-subscriber-meters" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-subscriber-meters"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-subscriber-meters"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-subscriber-meters" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-subscriber-meters">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-subscriber-meters" data-method="GET"
      data-path="api/v1/subscriber-meters"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-subscriber-meters', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-subscriber-meters"
                    onclick="tryItOut('GETapi-v1-subscriber-meters');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-subscriber-meters"
                    onclick="cancelTryOut('GETapi-v1-subscriber-meters');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-subscriber-meters"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/subscriber-meters</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-subscriber-meters"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-subscriber-meters"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-subscriber-meters--subscriber_meter_id-">GET api/v1/subscriber-meters/{subscriber_meter_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-subscriber-meters--subscriber_meter_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/subscriber-meters/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriber-meters/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-subscriber-meters--subscriber_meter_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-subscriber-meters--subscriber_meter_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-subscriber-meters--subscriber_meter_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-subscriber-meters--subscriber_meter_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-subscriber-meters--subscriber_meter_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-subscriber-meters--subscriber_meter_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-subscriber-meters--subscriber_meter_id-" data-method="GET"
      data-path="api/v1/subscriber-meters/{subscriber_meter_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-subscriber-meters--subscriber_meter_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-subscriber-meters--subscriber_meter_id-"
                    onclick="tryItOut('GETapi-v1-subscriber-meters--subscriber_meter_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-subscriber-meters--subscriber_meter_id-"
                    onclick="cancelTryOut('GETapi-v1-subscriber-meters--subscriber_meter_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-subscriber-meters--subscriber_meter_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/subscriber-meters/{subscriber_meter_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-subscriber-meters--subscriber_meter_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-subscriber-meters--subscriber_meter_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subscriber_meter_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscriber_meter_id"                data-endpoint="GETapi-v1-subscriber-meters--subscriber_meter_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the subscriber meter. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-subscriber-meters">POST api/v1/subscriber-meters</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-subscriber-meters">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/subscriber-meters" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"meter_number\": \"vmqeopfuudtdsufvyvddq\",
    \"property_label\": \"amniihfqcoynlazghdtqt\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriber-meters"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "meter_number": "vmqeopfuudtdsufvyvddq",
    "property_label": "amniihfqcoynlazghdtqt"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-subscriber-meters">
</span>
<span id="execution-results-POSTapi-v1-subscriber-meters" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-subscriber-meters"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-subscriber-meters"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-subscriber-meters" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-subscriber-meters">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-subscriber-meters" data-method="POST"
      data-path="api/v1/subscriber-meters"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-subscriber-meters', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-subscriber-meters"
                    onclick="tryItOut('POSTapi-v1-subscriber-meters');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-subscriber-meters"
                    onclick="cancelTryOut('POSTapi-v1-subscriber-meters');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-subscriber-meters"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/subscriber-meters</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-subscriber-meters"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-subscriber-meters"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>meter_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="meter_number"                data-endpoint="POSTapi-v1-subscriber-meters"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>property_label</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="property_label"                data-endpoint="POSTapi-v1-subscriber-meters"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>Must not be greater than 150 characters. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-subscriber-meters--subscriber_meter_id-">DELETE api/v1/subscriber-meters/{subscriber_meter_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-subscriber-meters--subscriber_meter_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/subscriber-meters/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriber-meters/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-subscriber-meters--subscriber_meter_id-">
</span>
<span id="execution-results-DELETEapi-v1-subscriber-meters--subscriber_meter_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-subscriber-meters--subscriber_meter_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-subscriber-meters--subscriber_meter_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-subscriber-meters--subscriber_meter_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-subscriber-meters--subscriber_meter_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-subscriber-meters--subscriber_meter_id-" data-method="DELETE"
      data-path="api/v1/subscriber-meters/{subscriber_meter_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-subscriber-meters--subscriber_meter_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-subscriber-meters--subscriber_meter_id-"
                    onclick="tryItOut('DELETEapi-v1-subscriber-meters--subscriber_meter_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-subscriber-meters--subscriber_meter_id-"
                    onclick="cancelTryOut('DELETEapi-v1-subscriber-meters--subscriber_meter_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-subscriber-meters--subscriber_meter_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/subscriber-meters/{subscriber_meter_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-subscriber-meters--subscriber_meter_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-subscriber-meters--subscriber_meter_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subscriber_meter_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscriber_meter_id"                data-endpoint="DELETEapi-v1-subscriber-meters--subscriber_meter_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the subscriber meter. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-subscriber-meters--subscriber_meter_id--qr">GET api/v1/subscriber-meters/{subscriber_meter_id}/qr</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-subscriber-meters--subscriber_meter_id--qr">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/subscriber-meters/17/qr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriber-meters/17/qr"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-subscriber-meters--subscriber_meter_id--qr">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-subscriber-meters--subscriber_meter_id--qr" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-subscriber-meters--subscriber_meter_id--qr"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-subscriber-meters--subscriber_meter_id--qr"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-subscriber-meters--subscriber_meter_id--qr" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-subscriber-meters--subscriber_meter_id--qr">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-subscriber-meters--subscriber_meter_id--qr" data-method="GET"
      data-path="api/v1/subscriber-meters/{subscriber_meter_id}/qr"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-subscriber-meters--subscriber_meter_id--qr', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-subscriber-meters--subscriber_meter_id--qr"
                    onclick="tryItOut('GETapi-v1-subscriber-meters--subscriber_meter_id--qr');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-subscriber-meters--subscriber_meter_id--qr"
                    onclick="cancelTryOut('GETapi-v1-subscriber-meters--subscriber_meter_id--qr');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-subscriber-meters--subscriber_meter_id--qr"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/subscriber-meters/{subscriber_meter_id}/qr</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-subscriber-meters--subscriber_meter_id--qr"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-subscriber-meters--subscriber_meter_id--qr"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subscriber_meter_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscriber_meter_id"                data-endpoint="GETapi-v1-subscriber-meters--subscriber_meter_id--qr"
               value="17"
               data-component="url">
    <br>
<p>The ID of the subscriber meter. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-invoices--invoice_id--verify">GET api/v1/invoices/{invoice_id}/verify</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-invoices--invoice_id--verify">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/invoices/17/verify" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/invoices/17/verify"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-invoices--invoice_id--verify">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 60
x-ratelimit-remaining: 57
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;العنصر المطلوب غير موجود.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-invoices--invoice_id--verify" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-invoices--invoice_id--verify"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-invoices--invoice_id--verify"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-invoices--invoice_id--verify" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-invoices--invoice_id--verify">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-invoices--invoice_id--verify" data-method="GET"
      data-path="api/v1/invoices/{invoice_id}/verify"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-invoices--invoice_id--verify', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-invoices--invoice_id--verify"
                    onclick="tryItOut('GETapi-v1-invoices--invoice_id--verify');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-invoices--invoice_id--verify"
                    onclick="cancelTryOut('GETapi-v1-invoices--invoice_id--verify');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-invoices--invoice_id--verify"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/invoices/{invoice_id}/verify</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-invoices--invoice_id--verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-invoices--invoice_id--verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>invoice_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="invoice_id"                data-endpoint="GETapi-v1-invoices--invoice_id--verify"
               value="17"
               data-component="url">
    <br>
<p>The ID of the invoice. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="adar-almstkhdmyn-osgl-altdkyk">إدارة المستخدمين وسجل التدقيق</h1>

    

                                <h2 id="adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users">GET api/v1/users</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-users">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/users" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-users">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-users" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-users"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-users"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-users" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-users">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-users" data-method="GET"
      data-path="api/v1/users"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-users', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-users"
                    onclick="tryItOut('GETapi-v1-users');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-users"
                    onclick="cancelTryOut('GETapi-v1-users');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-users"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/users</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-users"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-users"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users-locked">GET api/v1/users/locked</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-users-locked">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/users/locked" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users/locked"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-users-locked">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-users-locked" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-users-locked"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-users-locked"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-users-locked" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-users-locked">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-users-locked" data-method="GET"
      data-path="api/v1/users/locked"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-users-locked', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-users-locked"
                    onclick="tryItOut('GETapi-v1-users-locked');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-users-locked"
                    onclick="cancelTryOut('GETapi-v1-users-locked');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-users-locked"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/users/locked</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-users-locked"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-users-locked"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-users--user_id-">GET api/v1/users/{user_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-users--user_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/users/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-users--user_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-users--user_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-users--user_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-users--user_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-users--user_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-users--user_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-users--user_id-" data-method="GET"
      data-path="api/v1/users/{user_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-users--user_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-users--user_id-"
                    onclick="tryItOut('GETapi-v1-users--user_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-users--user_id-"
                    onclick="cancelTryOut('GETapi-v1-users--user_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-users--user_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/users/{user_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="GETapi-v1-users--user_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="adar-almstkhdmyn-osgl-altdkyk-PATCHapi-v1-users--user_id-">PATCH api/v1/users/{user_id}</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-users--user_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/users/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"phone\": \"562771717728\",
    \"email\": \"dsmith@example.com\",
    \"status\": \"inactive\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "phone": "562771717728",
    "email": "dsmith@example.com",
    "status": "inactive"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-users--user_id-">
</span>
<span id="execution-results-PATCHapi-v1-users--user_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-users--user_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-users--user_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-users--user_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-users--user_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-users--user_id-" data-method="PATCH"
      data-path="api/v1/users/{user_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-users--user_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-users--user_id-"
                    onclick="tryItOut('PATCHapi-v1-users--user_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-users--user_id-"
                    onclick="cancelTryOut('PATCHapi-v1-users--user_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-users--user_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/users/{user_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="PATCHapi-v1-users--user_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PATCHapi-v1-users--user_id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must be at least 3 characters. Must not be greater than 100 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PATCHapi-v1-users--user_id-"
               value="562771717728"
               data-component="body">
    <br>
<p>Must match the regex /^+?[0-9]{7,15}$/. Example: <code>562771717728</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="PATCHapi-v1-users--user_id-"
               value="dsmith@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>dsmith@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-users--user_id-"
               value="inactive"
               data-component="body">
    <br>
<p>Example: <code>inactive</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>active</code></li> <li><code>inactive</code></li> <li><code>suspended</code></li></ul>
        </div>
        </form>

                    <h2 id="adar-almstkhdmyn-osgl-altdkyk-DELETEapi-v1-users--user_id-">DELETE api/v1/users/{user_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-users--user_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/users/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-users--user_id-">
</span>
<span id="execution-results-DELETEapi-v1-users--user_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-users--user_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-users--user_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-users--user_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-users--user_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-users--user_id-" data-method="DELETE"
      data-path="api/v1/users/{user_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-users--user_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-users--user_id-"
                    onclick="tryItOut('DELETEapi-v1-users--user_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-users--user_id-"
                    onclick="cancelTryOut('DELETEapi-v1-users--user_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-users--user_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/users/{user_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="DELETEapi-v1-users--user_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="adar-almstkhdmyn-osgl-altdkyk-PATCHapi-v1-users--user_id--unlock">PATCH api/v1/users/{user_id}/unlock</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-users--user_id--unlock">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/users/17/unlock" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/users/17/unlock"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-users--user_id--unlock">
</span>
<span id="execution-results-PATCHapi-v1-users--user_id--unlock" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-users--user_id--unlock"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-users--user_id--unlock"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-users--user_id--unlock" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-users--user_id--unlock">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-users--user_id--unlock" data-method="PATCH"
      data-path="api/v1/users/{user_id}/unlock"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-users--user_id--unlock', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-users--user_id--unlock"
                    onclick="tryItOut('PATCHapi-v1-users--user_id--unlock');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-users--user_id--unlock"
                    onclick="cancelTryOut('PATCHapi-v1-users--user_id--unlock');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-users--user_id--unlock"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/users/{user_id}/unlock</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-users--user_id--unlock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-users--user_id--unlock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="PATCHapi-v1-users--user_id--unlock"
               value="17"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="adar-almstkhdmyn-osgl-altdkyk-GETapi-v1-activity-logs">GET api/v1/activity-logs</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-activity-logs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/activity-logs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/activity-logs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-activity-logs">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-activity-logs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-activity-logs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-activity-logs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-activity-logs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-activity-logs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-activity-logs" data-method="GET"
      data-path="api/v1/activity-logs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-activity-logs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-activity-logs"
                    onclick="tryItOut('GETapi-v1-activity-logs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-activity-logs"
                    onclick="cancelTryOut('GETapi-v1-activity-logs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-activity-logs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/activity-logs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-activity-logs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-activity-logs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="adar-almoldat">إدارة المولدات</h1>

    

                                <h2 id="adar-almoldat-GETapi-v1-generators-available">GET api/v1/generators/available</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators-available">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/available" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"subscriber_meter_id\": 17,
    \"schedule\": \"custom\",
    \"service_start_time\": \"16:46\",
    \"service_end_time\": \"2107-08-20\",
    \"requested_capacity_kw\": 45
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/available"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "subscriber_meter_id": 17,
    "schedule": "custom",
    "service_start_time": "16:46",
    "service_end_time": "2107-08-20",
    "requested_capacity_kw": 45
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators-available">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators-available" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators-available"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators-available"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators-available" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators-available">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators-available" data-method="GET"
      data-path="api/v1/generators/available"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators-available', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators-available"
                    onclick="tryItOut('GETapi-v1-generators-available');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators-available"
                    onclick="cancelTryOut('GETapi-v1-generators-available');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators-available"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/available</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators-available"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators-available"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subscriber_meter_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscriber_meter_id"                data-endpoint="GETapi-v1-generators-available"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>schedule</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="schedule"                data-endpoint="GETapi-v1-generators-available"
               value="custom"
               data-component="body">
    <br>
<p>Example: <code>custom</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>day</code></li> <li><code>night</code></li> <li><code>24h</code></li> <li><code>custom</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>service_start_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="service_start_time"                data-endpoint="GETapi-v1-generators-available"
               value="16:46"
               data-component="body">
    <br>
<p>This field is required when <code>schedule</code> is <code>custom</code>. Must be a valid date in the format <code>H:i</code>. Example: <code>16:46</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>service_end_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="service_end_time"                data-endpoint="GETapi-v1-generators-available"
               value="2107-08-20"
               data-component="body">
    <br>
<p>This field is required when <code>schedule</code> is <code>custom</code>. Must be a valid date in the format <code>H:i</code>. Must be a date after <code>service_start_time</code>. Example: <code>2107-08-20</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>requested_capacity_kw</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="requested_capacity_kw"                data-endpoint="GETapi-v1-generators-available"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 0.1. Example: <code>45</code></p>
        </div>
        </form>

                    <h2 id="adar-almoldat-GETapi-v1-generators">GET api/v1/generators</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators" data-method="GET"
      data-path="api/v1/generators"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators"
                    onclick="tryItOut('GETapi-v1-generators');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators"
                    onclick="cancelTryOut('GETapi-v1-generators');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="adar-almoldat-POSTapi-v1-generators">POST api/v1/generators</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-generators">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/generators" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"price_per_kw\": 1,
    \"currency\": \"USD\",
    \"capacity_kw\": 45,
    \"location_id\": 17,
    \"status\": \"inactive\",
    \"operating_schedule\": \"day\",
    \"operating_start_time\": \"16:46\",
    \"operating_end_time\": \"2107-08-20\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "price_per_kw": 1,
    "currency": "USD",
    "capacity_kw": 45,
    "location_id": 17,
    "status": "inactive",
    "operating_schedule": "day",
    "operating_start_time": "16:46",
    "operating_end_time": "2107-08-20"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-generators">
</span>
<span id="execution-results-POSTapi-v1-generators" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-generators"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-generators"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-generators" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-generators">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-generators" data-method="POST"
      data-path="api/v1/generators"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-generators', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-generators"
                    onclick="tryItOut('POSTapi-v1-generators');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-generators"
                    onclick="cancelTryOut('POSTapi-v1-generators');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-generators"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/generators</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-generators"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-generators"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-generators"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 150 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price_per_kw</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price_per_kw"                data-endpoint="POSTapi-v1-generators"
               value="1"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 9999.99. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-generators"
               value="USD"
               data-component="body">
    <br>
<p>Example: <code>USD</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ILS</code></li> <li><code>USD</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>capacity_kw</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="capacity_kw"                data-endpoint="POSTapi-v1-generators"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>location_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="location_id"                data-endpoint="POSTapi-v1-generators"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="POSTapi-v1-generators"
               value="inactive"
               data-component="body">
    <br>
<p>Example: <code>inactive</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>active</code></li> <li><code>inactive</code></li> <li><code>maintenance</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>operating_schedule</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="operating_schedule"                data-endpoint="POSTapi-v1-generators"
               value="day"
               data-component="body">
    <br>
<p>Example: <code>day</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>day</code></li> <li><code>night</code></li> <li><code>24h</code></li> <li><code>custom</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>operating_start_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="operating_start_time"                data-endpoint="POSTapi-v1-generators"
               value="16:46"
               data-component="body">
    <br>
<p>This field is required when <code>operating_schedule</code> is <code>custom</code>. Must be a valid date in the format <code>H:i</code>. Example: <code>16:46</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>operating_end_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="operating_end_time"                data-endpoint="POSTapi-v1-generators"
               value="2107-08-20"
               data-component="body">
    <br>
<p>This field is required when <code>operating_schedule</code> is <code>custom</code>. Must be a valid date in the format <code>H:i</code>. Must be a date after <code>operating_start_time</code>. Example: <code>2107-08-20</code></p>
        </div>
        </form>

                    <h2 id="adar-almoldat-GETapi-v1-generators--generator_id-">GET api/v1/generators/{generator_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id-" data-method="GET"
      data-path="api/v1/generators/{generator_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id-"
                    onclick="tryItOut('GETapi-v1-generators--generator_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id-"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="adar-almoldat-PATCHapi-v1-generators--generator_id-">PATCH api/v1/generators/{generator_id}</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-generators--generator_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/generators/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"price_per_kw\": 1,
    \"currency\": \"ILS\",
    \"capacity_kw\": 45,
    \"location_id\": 17,
    \"status\": \"active\",
    \"operating_schedule\": \"day\",
    \"operating_start_time\": \"16:46\",
    \"operating_end_time\": \"2107-08-20\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "price_per_kw": 1,
    "currency": "ILS",
    "capacity_kw": 45,
    "location_id": 17,
    "status": "active",
    "operating_schedule": "day",
    "operating_start_time": "16:46",
    "operating_end_time": "2107-08-20"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-generators--generator_id-">
</span>
<span id="execution-results-PATCHapi-v1-generators--generator_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-generators--generator_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-generators--generator_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-generators--generator_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-generators--generator_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-generators--generator_id-" data-method="PATCH"
      data-path="api/v1/generators/{generator_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-generators--generator_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-generators--generator_id-"
                    onclick="tryItOut('PATCHapi-v1-generators--generator_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-generators--generator_id-"
                    onclick="cancelTryOut('PATCHapi-v1-generators--generator_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-generators--generator_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/generators/{generator_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 150 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price_per_kw</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price_per_kw"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="1"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 9999.99. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="ILS"
               data-component="body">
    <br>
<p>Example: <code>ILS</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ILS</code></li> <li><code>USD</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>capacity_kw</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="capacity_kw"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>location_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="location_id"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="active"
               data-component="body">
    <br>
<p>Example: <code>active</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>active</code></li> <li><code>inactive</code></li> <li><code>maintenance</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>operating_schedule</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="operating_schedule"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="day"
               data-component="body">
    <br>
<p>Example: <code>day</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>day</code></li> <li><code>night</code></li> <li><code>24h</code></li> <li><code>custom</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>operating_start_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="operating_start_time"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="16:46"
               data-component="body">
    <br>
<p>This field is required when <code>operating_schedule</code> is <code>custom</code>. Must be a valid date in the format <code>H:i</code>. Example: <code>16:46</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>operating_end_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="operating_end_time"                data-endpoint="PATCHapi-v1-generators--generator_id-"
               value="2107-08-20"
               data-component="body">
    <br>
<p>This field is required when <code>operating_schedule</code> is <code>custom</code>. Must be a valid date in the format <code>H:i</code>. Must be a date after <code>operating_start_time</code>. Example: <code>2107-08-20</code></p>
        </div>
        </form>

                    <h2 id="adar-almoldat-DELETEapi-v1-generators--generator_id-">DELETE api/v1/generators/{generator_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-generators--generator_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/generators/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-generators--generator_id-">
</span>
<span id="execution-results-DELETEapi-v1-generators--generator_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-generators--generator_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-generators--generator_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-generators--generator_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-generators--generator_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-generators--generator_id-" data-method="DELETE"
      data-path="api/v1/generators/{generator_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-generators--generator_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-generators--generator_id-"
                    onclick="tryItOut('DELETEapi-v1-generators--generator_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-generators--generator_id-"
                    onclick="cancelTryOut('DELETEapi-v1-generators--generator_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-generators--generator_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/generators/{generator_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-generators--generator_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-generators--generator_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="DELETEapi-v1-generators--generator_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="adar-almoldat-GETapi-v1-generators--generator_id--qr">GET api/v1/generators/{generator_id}/qr</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--qr">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/qr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/qr"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--qr">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--qr" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--qr"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--qr"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--qr" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--qr">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--qr" data-method="GET"
      data-path="api/v1/generators/{generator_id}/qr"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--qr', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--qr"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--qr');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--qr"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--qr');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--qr"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/qr</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--qr"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--qr"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--qr"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="adar-almoldat-GETapi-v1-generators--generator_id--attachments">GET api/v1/generators/{generator_id}/attachments</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--attachments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/attachments" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/attachments"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--attachments">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--attachments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--attachments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--attachments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--attachments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--attachments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--attachments" data-method="GET"
      data-path="api/v1/generators/{generator_id}/attachments"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--attachments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--attachments"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--attachments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--attachments"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--attachments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--attachments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/attachments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--attachments"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="adar-almoldat-POSTapi-v1-generators--generator_id--attachments">POST api/v1/generators/{generator_id}/attachments</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-generators--generator_id--attachments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/generators/17/attachments" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "document_type=generator_purchase_invoice"\
    --form "description=Dolores dolorum amet iste laborum eius est dolor."\
    --form "file=@C:\Users\Aya\AppData\Local\Temp\php6D5B.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/attachments"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('document_type', 'generator_purchase_invoice');
body.append('description', 'Dolores dolorum amet iste laborum eius est dolor.');
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-generators--generator_id--attachments">
</span>
<span id="execution-results-POSTapi-v1-generators--generator_id--attachments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-generators--generator_id--attachments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-generators--generator_id--attachments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-generators--generator_id--attachments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-generators--generator_id--attachments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-generators--generator_id--attachments" data-method="POST"
      data-path="api/v1/generators/{generator_id}/attachments"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-generators--generator_id--attachments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-generators--generator_id--attachments"
                    onclick="tryItOut('POSTapi-v1-generators--generator_id--attachments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-generators--generator_id--attachments"
                    onclick="cancelTryOut('POSTapi-v1-generators--generator_id--attachments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-generators--generator_id--attachments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/generators/{generator_id}/attachments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-generators--generator_id--attachments"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-generators--generator_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-generators--generator_id--attachments"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>document_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="document_type"                data-endpoint="POSTapi-v1-generators--generator_id--attachments"
               value="generator_purchase_invoice"
               data-component="body">
    <br>
<p>Example: <code>generator_purchase_invoice</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>generator_photo</code></li> <li><code>generator_license</code></li> <li><code>generator_purchase_invoice</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-generators--generator_id--attachments"
               value=""
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 5120 kilobytes. Example: <code>C:\Users\Aya\AppData\Local\Temp\php6D5B.tmp</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-generators--generator_id--attachments"
               value="Dolores dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>Dolores dolorum amet iste laborum eius est dolor.</code></p>
        </div>
        </form>

                <h1 id="alaaatal-oaltokaaat-althky">الأعطال والتوقعات الذكية</h1>

    

                                <h2 id="alaaatal-oaltokaaat-althky-GETapi-v1-faults">GET api/v1/faults</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-faults">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/faults" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/faults"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-faults">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-faults" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-faults"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-faults"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-faults" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-faults">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-faults" data-method="GET"
      data-path="api/v1/faults"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-faults', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-faults"
                    onclick="tryItOut('GETapi-v1-faults');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-faults"
                    onclick="cancelTryOut('GETapi-v1-faults');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-faults"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/faults</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-faults"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-faults"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alaaatal-oaltokaaat-althky-POSTapi-v1-faults">POST api/v1/faults</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-faults">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/faults" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"generator_id\": 17,
    \"title\": \"mqeopfuudtdsufvyvddqa\",
    \"description\": \"Molestias ipsam sit veniam sed fuga aspernatur.\",
    \"priority\": \"medium\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/faults"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "generator_id": 17,
    "title": "mqeopfuudtdsufvyvddqa",
    "description": "Molestias ipsam sit veniam sed fuga aspernatur.",
    "priority": "medium"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-faults">
</span>
<span id="execution-results-POSTapi-v1-faults" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-faults"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-faults"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-faults" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-faults">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-faults" data-method="POST"
      data-path="api/v1/faults"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-faults', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-faults"
                    onclick="tryItOut('POSTapi-v1-faults');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-faults"
                    onclick="cancelTryOut('POSTapi-v1-faults');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-faults"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/faults</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-faults"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-faults"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-faults"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-v1-faults"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 150 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-faults"
               value="Molestias ipsam sit veniam sed fuga aspernatur."
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>Molestias ipsam sit veniam sed fuga aspernatur.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="priority"                data-endpoint="POSTapi-v1-faults"
               value="medium"
               data-component="body">
    <br>
<p>Example: <code>medium</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>low</code></li> <li><code>medium</code></li> <li><code>high</code></li> <li><code>critical</code></li></ul>
        </div>
        </form>

                    <h2 id="alaaatal-oaltokaaat-althky-GETapi-v1-faults--fault_id-">GET api/v1/faults/{fault_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-faults--fault_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/faults/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/faults/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-faults--fault_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-faults--fault_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-faults--fault_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-faults--fault_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-faults--fault_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-faults--fault_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-faults--fault_id-" data-method="GET"
      data-path="api/v1/faults/{fault_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-faults--fault_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-faults--fault_id-"
                    onclick="tryItOut('GETapi-v1-faults--fault_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-faults--fault_id-"
                    onclick="cancelTryOut('GETapi-v1-faults--fault_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-faults--fault_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/faults/{fault_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-faults--fault_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-faults--fault_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>fault_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fault_id"                data-endpoint="GETapi-v1-faults--fault_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the fault. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--verify">PATCH api/v1/faults/{fault_id}/verify</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-faults--fault_id--verify">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/faults/17/verify" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"is_valid\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/faults/17/verify"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "is_valid": false
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-faults--fault_id--verify">
</span>
<span id="execution-results-PATCHapi-v1-faults--fault_id--verify" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-faults--fault_id--verify"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-faults--fault_id--verify"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-faults--fault_id--verify" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-faults--fault_id--verify">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-faults--fault_id--verify" data-method="PATCH"
      data-path="api/v1/faults/{fault_id}/verify"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-faults--fault_id--verify', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-faults--fault_id--verify"
                    onclick="tryItOut('PATCHapi-v1-faults--fault_id--verify');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-faults--fault_id--verify"
                    onclick="cancelTryOut('PATCHapi-v1-faults--fault_id--verify');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-faults--fault_id--verify"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/faults/{fault_id}/verify</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-faults--fault_id--verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-faults--fault_id--verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>fault_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fault_id"                data-endpoint="PATCHapi-v1-faults--fault_id--verify"
               value="17"
               data-component="url">
    <br>
<p>The ID of the fault. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_valid</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
 &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-faults--fault_id--verify" style="display: none">
            <input type="radio" name="is_valid"
                   value="true"
                   data-endpoint="PATCHapi-v1-faults--fault_id--verify"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-faults--fault_id--verify" style="display: none">
            <input type="radio" name="is_valid"
                   value="false"
                   data-endpoint="PATCHapi-v1-faults--fault_id--verify"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
        </form>

                    <h2 id="alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--decide-repair">PATCH api/v1/faults/{fault_id}/decide-repair</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-faults--fault_id--decide-repair">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/faults/17/decide-repair" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"repair_method\": \"internal_technician\",
    \"technician_id\": 17,
    \"instructions\": \"mqeopfuudtdsufvyvddqa\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/faults/17/decide-repair"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "repair_method": "internal_technician",
    "technician_id": 17,
    "instructions": "mqeopfuudtdsufvyvddqa"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-faults--fault_id--decide-repair">
</span>
<span id="execution-results-PATCHapi-v1-faults--fault_id--decide-repair" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-faults--fault_id--decide-repair"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-faults--fault_id--decide-repair"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-faults--fault_id--decide-repair" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-faults--fault_id--decide-repair">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-faults--fault_id--decide-repair" data-method="PATCH"
      data-path="api/v1/faults/{fault_id}/decide-repair"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-faults--fault_id--decide-repair', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-faults--fault_id--decide-repair"
                    onclick="tryItOut('PATCHapi-v1-faults--fault_id--decide-repair');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-faults--fault_id--decide-repair"
                    onclick="cancelTryOut('PATCHapi-v1-faults--fault_id--decide-repair');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-faults--fault_id--decide-repair"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/faults/{fault_id}/decide-repair</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-faults--fault_id--decide-repair"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-faults--fault_id--decide-repair"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>fault_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fault_id"                data-endpoint="PATCHapi-v1-faults--fault_id--decide-repair"
               value="17"
               data-component="url">
    <br>
<p>The ID of the fault. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>repair_method</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="repair_method"                data-endpoint="PATCHapi-v1-faults--fault_id--decide-repair"
               value="internal_technician"
               data-component="body">
    <br>
<p>Example: <code>internal_technician</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>owner_fixed</code></li> <li><code>internal_technician</code></li> <li><code>platform_technician</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>technician_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_id"                data-endpoint="PATCHapi-v1-faults--fault_id--decide-repair"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>instructions</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="instructions"                data-endpoint="PATCHapi-v1-faults--fault_id--decide-repair"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
        </form>

                    <h2 id="alaaatal-oaltokaaat-althky-PATCHapi-v1-faults--fault_id--override-status">PATCH api/v1/faults/{fault_id}/override-status</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-faults--fault_id--override-status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/faults/17/override-status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"verified\",
    \"admin_override_reason\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/faults/17/override-status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "verified",
    "admin_override_reason": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-faults--fault_id--override-status">
</span>
<span id="execution-results-PATCHapi-v1-faults--fault_id--override-status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-faults--fault_id--override-status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-faults--fault_id--override-status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-faults--fault_id--override-status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-faults--fault_id--override-status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-faults--fault_id--override-status" data-method="PATCH"
      data-path="api/v1/faults/{fault_id}/override-status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-faults--fault_id--override-status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-faults--fault_id--override-status"
                    onclick="tryItOut('PATCHapi-v1-faults--fault_id--override-status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-faults--fault_id--override-status"
                    onclick="cancelTryOut('PATCHapi-v1-faults--fault_id--override-status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-faults--fault_id--override-status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/faults/{fault_id}/override-status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-faults--fault_id--override-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-faults--fault_id--override-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>fault_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fault_id"                data-endpoint="PATCHapi-v1-faults--fault_id--override-status"
               value="17"
               data-component="url">
    <br>
<p>The ID of the fault. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-faults--fault_id--override-status"
               value="verified"
               data-component="body">
    <br>
<p>Example: <code>verified</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>pending_verification</code></li> <li><code>verified</code></li> <li><code>rejected</code></li> <li><code>in_repair</code></li> <li><code>resolved</code></li> <li><code>closed</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>admin_override_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="admin_override_reason"                data-endpoint="PATCHapi-v1-faults--fault_id--override-status"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                    <h2 id="alaaatal-oaltokaaat-althky-DELETEapi-v1-faults--fault_id-">DELETE api/v1/faults/{fault_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-faults--fault_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/faults/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/faults/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-faults--fault_id-">
</span>
<span id="execution-results-DELETEapi-v1-faults--fault_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-faults--fault_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-faults--fault_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-faults--fault_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-faults--fault_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-faults--fault_id-" data-method="DELETE"
      data-path="api/v1/faults/{fault_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-faults--fault_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-faults--fault_id-"
                    onclick="tryItOut('DELETEapi-v1-faults--fault_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-faults--fault_id-"
                    onclick="cancelTryOut('DELETEapi-v1-faults--fault_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-faults--fault_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/faults/{fault_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-faults--fault_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-faults--fault_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>fault_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fault_id"                data-endpoint="DELETEapi-v1-faults--fault_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the fault. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alaaatal-oaltokaaat-althky-GETapi-v1-fault-predictions">GET api/v1/fault-predictions</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-fault-predictions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/fault-predictions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/fault-predictions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-fault-predictions">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-fault-predictions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-fault-predictions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-fault-predictions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-fault-predictions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-fault-predictions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-fault-predictions" data-method="GET"
      data-path="api/v1/fault-predictions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-fault-predictions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-fault-predictions"
                    onclick="tryItOut('GETapi-v1-fault-predictions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-fault-predictions"
                    onclick="cancelTryOut('GETapi-v1-fault-predictions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-fault-predictions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/fault-predictions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-fault-predictions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-fault-predictions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alaaatal-oaltokaaat-althky-GETapi-v1-fault-predictions--fault_prediction_id-">GET api/v1/fault-predictions/{fault_prediction_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-fault-predictions--fault_prediction_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/fault-predictions/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/fault-predictions/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-fault-predictions--fault_prediction_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-fault-predictions--fault_prediction_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-fault-predictions--fault_prediction_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-fault-predictions--fault_prediction_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-fault-predictions--fault_prediction_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-fault-predictions--fault_prediction_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-fault-predictions--fault_prediction_id-" data-method="GET"
      data-path="api/v1/fault-predictions/{fault_prediction_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-fault-predictions--fault_prediction_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-fault-predictions--fault_prediction_id-"
                    onclick="tryItOut('GETapi-v1-fault-predictions--fault_prediction_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-fault-predictions--fault_prediction_id-"
                    onclick="cancelTryOut('GETapi-v1-fault-predictions--fault_prediction_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-fault-predictions--fault_prediction_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/fault-predictions/{fault_prediction_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-fault-predictions--fault_prediction_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-fault-predictions--fault_prediction_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>fault_prediction_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fault_prediction_id"                data-endpoint="GETapi-v1-fault-predictions--fault_prediction_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the fault prediction. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alaaatal-oaltokaaat-althky-POSTapi-v1-fault-predictions">POST api/v1/fault-predictions</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-fault-predictions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/fault-predictions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"generator_id\": 17,
    \"prediction_type\": \"mqeopfuudtdsufvyvddqa\",
    \"confidence\": 0,
    \"recommendation\": \"niihfqcoynlazghdtqtqx\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/fault-predictions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "generator_id": 17,
    "prediction_type": "mqeopfuudtdsufvyvddqa",
    "confidence": 0,
    "recommendation": "niihfqcoynlazghdtqtqx"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-fault-predictions">
</span>
<span id="execution-results-POSTapi-v1-fault-predictions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-fault-predictions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-fault-predictions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-fault-predictions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-fault-predictions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-fault-predictions" data-method="POST"
      data-path="api/v1/fault-predictions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-fault-predictions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-fault-predictions"
                    onclick="tryItOut('POSTapi-v1-fault-predictions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-fault-predictions"
                    onclick="cancelTryOut('POSTapi-v1-fault-predictions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-fault-predictions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/fault-predictions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-fault-predictions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-fault-predictions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-fault-predictions"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>prediction_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="prediction_type"                data-endpoint="POSTapi-v1-fault-predictions"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>confidence</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="confidence"                data-endpoint="POSTapi-v1-fault-predictions"
               value="0"
               data-component="body">
    <br>
<p>Must be between 0 and 1. Example: <code>0</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>recommendation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="recommendation"                data-endpoint="POSTapi-v1-fault-predictions"
               value="niihfqcoynlazghdtqtqx"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>niihfqcoynlazghdtqtqx</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>input_snapshot</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="input_snapshot"                data-endpoint="POSTapi-v1-fault-predictions"
               value=""
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="alaaatal-oaltokaaat-althky-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm">PATCH api/v1/fault-predictions/{fault_prediction_id}/confirm</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/fault-predictions/17/confirm" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/fault-predictions/17/confirm"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm">
</span>
<span id="execution-results-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm" data-method="PATCH"
      data-path="api/v1/fault-predictions/{fault_prediction_id}/confirm"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-fault-predictions--fault_prediction_id--confirm', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm"
                    onclick="tryItOut('PATCHapi-v1-fault-predictions--fault_prediction_id--confirm');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm"
                    onclick="cancelTryOut('PATCHapi-v1-fault-predictions--fault_prediction_id--confirm');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-fault-predictions--fault_prediction_id--confirm"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/fault-predictions/{fault_prediction_id}/confirm</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-fault-predictions--fault_prediction_id--confirm"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-fault-predictions--fault_prediction_id--confirm"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>fault_prediction_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fault_prediction_id"                data-endpoint="PATCHapi-v1-fault-predictions--fault_prediction_id--confirm"
               value="17"
               data-component="url">
    <br>
<p>The ID of the fault prediction. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alaaatal-oaltokaaat-althky-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss">PATCH api/v1/fault-predictions/{fault_prediction_id}/dismiss</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/fault-predictions/17/dismiss" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/fault-predictions/17/dismiss"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss">
</span>
<span id="execution-results-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss" data-method="PATCH"
      data-path="api/v1/fault-predictions/{fault_prediction_id}/dismiss"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss"
                    onclick="tryItOut('PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss"
                    onclick="cancelTryOut('PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/fault-predictions/{fault_prediction_id}/dismiss</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>fault_prediction_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fault_prediction_id"                data-endpoint="PATCHapi-v1-fault-predictions--fault_prediction_id--dismiss"
               value="17"
               data-component="url">
    <br>
<p>The ID of the fault prediction. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="alashaaarat">الإشعارات</h1>

    

                                <h2 id="alashaaarat-GETapi-v1-notifications">GET api/v1/notifications</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-notifications">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/notifications" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/notifications"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-notifications">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-notifications" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-notifications"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-notifications"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-notifications" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-notifications">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-notifications" data-method="GET"
      data-path="api/v1/notifications"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-notifications', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-notifications"
                    onclick="tryItOut('GETapi-v1-notifications');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-notifications"
                    onclick="cancelTryOut('GETapi-v1-notifications');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-notifications"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/notifications</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alashaaarat-PATCHapi-v1-notifications-read-all">PATCH api/v1/notifications/read-all</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-notifications-read-all">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/notifications/read-all" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/notifications/read-all"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-notifications-read-all">
</span>
<span id="execution-results-PATCHapi-v1-notifications-read-all" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-notifications-read-all"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-notifications-read-all"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-notifications-read-all" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-notifications-read-all">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-notifications-read-all" data-method="PATCH"
      data-path="api/v1/notifications/read-all"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-notifications-read-all', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-notifications-read-all"
                    onclick="tryItOut('PATCHapi-v1-notifications-read-all');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-notifications-read-all"
                    onclick="cancelTryOut('PATCHapi-v1-notifications-read-all');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-notifications-read-all"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/notifications/read-all</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-notifications-read-all"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-notifications-read-all"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alashaaarat-PATCHapi-v1-notifications--notification--read">PATCH api/v1/notifications/{notification}/read</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-notifications--notification--read">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/notifications/consequatur/read" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/notifications/consequatur/read"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-notifications--notification--read">
</span>
<span id="execution-results-PATCHapi-v1-notifications--notification--read" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-notifications--notification--read"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-notifications--notification--read"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-notifications--notification--read" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-notifications--notification--read">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-notifications--notification--read" data-method="PATCH"
      data-path="api/v1/notifications/{notification}/read"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-notifications--notification--read', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-notifications--notification--read"
                    onclick="tryItOut('PATCHapi-v1-notifications--notification--read');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-notifications--notification--read"
                    onclick="cancelTryOut('PATCHapi-v1-notifications--notification--read');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-notifications--notification--read"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/notifications/{notification}/read</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-notifications--notification--read"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-notifications--notification--read"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>notification</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notification"                data-endpoint="PATCHapi-v1-notifications--notification--read"
               value="consequatur"
               data-component="url">
    <br>
<p>The notification. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="alashaaarat-DELETEapi-v1-notifications--notification-">DELETE api/v1/notifications/{notification}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-notifications--notification-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/notifications/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/notifications/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-notifications--notification-">
</span>
<span id="execution-results-DELETEapi-v1-notifications--notification-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-notifications--notification-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-notifications--notification-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-notifications--notification-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-notifications--notification-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-notifications--notification-" data-method="DELETE"
      data-path="api/v1/notifications/{notification}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-notifications--notification-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-notifications--notification-"
                    onclick="tryItOut('DELETEapi-v1-notifications--notification-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-notifications--notification-"
                    onclick="cancelTryOut('DELETEapi-v1-notifications--notification-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-notifications--notification-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/notifications/{notification}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-notifications--notification-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-notifications--notification-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>notification</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notification"                data-endpoint="DELETEapi-v1-notifications--notification-"
               value="consequatur"
               data-component="url">
    <br>
<p>The notification. Example: <code>consequatur</code></p>
            </div>
                    </form>

                <h1 id="alashtrakat-otlbat-alkhdm">الاشتراكات وطلبات الخدمة</h1>

    

                                <h2 id="alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions">GET api/v1/subscriptions</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-subscriptions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/subscriptions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriptions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-subscriptions">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-subscriptions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-subscriptions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-subscriptions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-subscriptions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-subscriptions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-subscriptions" data-method="GET"
      data-path="api/v1/subscriptions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-subscriptions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-subscriptions"
                    onclick="tryItOut('GETapi-v1-subscriptions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-subscriptions"
                    onclick="cancelTryOut('GETapi-v1-subscriptions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-subscriptions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/subscriptions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-subscriptions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-subscriptions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions--subscription_id-">GET api/v1/subscriptions/{subscription_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-subscriptions--subscription_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/subscriptions/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriptions/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-subscriptions--subscription_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-subscriptions--subscription_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-subscriptions--subscription_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-subscriptions--subscription_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-subscriptions--subscription_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-subscriptions--subscription_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-subscriptions--subscription_id-" data-method="GET"
      data-path="api/v1/subscriptions/{subscription_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-subscriptions--subscription_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-subscriptions--subscription_id-"
                    onclick="tryItOut('GETapi-v1-subscriptions--subscription_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-subscriptions--subscription_id-"
                    onclick="cancelTryOut('GETapi-v1-subscriptions--subscription_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-subscriptions--subscription_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/subscriptions/{subscription_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-subscriptions--subscription_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-subscriptions--subscription_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subscription_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscription_id"                data-endpoint="GETapi-v1-subscriptions--subscription_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the subscription. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-POSTapi-v1-subscriptions">POST api/v1/subscriptions</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-subscriptions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/subscriptions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"subscriber_meter_id\": 17,
    \"generator_id\": 17,
    \"requested_capacity_kw\": 45,
    \"schedule\": \"custom\",
    \"billing_cycle\": \"weekly\",
    \"service_start_time\": \"16:46\",
    \"service_end_time\": \"2107-08-20\",
    \"contract_type\": \"mqeopfuudtdsufvyvddqa\",
    \"start_date\": \"2026-07-21T16:46:00\",
    \"end_date\": \"2107-08-20\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriptions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "subscriber_meter_id": 17,
    "generator_id": 17,
    "requested_capacity_kw": 45,
    "schedule": "custom",
    "billing_cycle": "weekly",
    "service_start_time": "16:46",
    "service_end_time": "2107-08-20",
    "contract_type": "mqeopfuudtdsufvyvddqa",
    "start_date": "2026-07-21T16:46:00",
    "end_date": "2107-08-20"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-subscriptions">
</span>
<span id="execution-results-POSTapi-v1-subscriptions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-subscriptions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-subscriptions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-subscriptions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-subscriptions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-subscriptions" data-method="POST"
      data-path="api/v1/subscriptions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-subscriptions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-subscriptions"
                    onclick="tryItOut('POSTapi-v1-subscriptions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-subscriptions"
                    onclick="cancelTryOut('POSTapi-v1-subscriptions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-subscriptions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/subscriptions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-subscriptions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-subscriptions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subscriber_meter_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscriber_meter_id"                data-endpoint="POSTapi-v1-subscriptions"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-subscriptions"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>requested_capacity_kw</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="requested_capacity_kw"                data-endpoint="POSTapi-v1-subscriptions"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 0.1. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>schedule</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="schedule"                data-endpoint="POSTapi-v1-subscriptions"
               value="custom"
               data-component="body">
    <br>
<p>Example: <code>custom</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>day</code></li> <li><code>night</code></li> <li><code>24h</code></li> <li><code>custom</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>billing_cycle</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="billing_cycle"                data-endpoint="POSTapi-v1-subscriptions"
               value="weekly"
               data-component="body">
    <br>
<p>Example: <code>weekly</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>daily</code></li> <li><code>weekly</code></li> <li><code>monthly</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>service_start_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="service_start_time"                data-endpoint="POSTapi-v1-subscriptions"
               value="16:46"
               data-component="body">
    <br>
<p>This field is required when <code>schedule</code> is <code>custom</code>. Must be a valid date in the format <code>H:i</code>. Example: <code>16:46</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>service_end_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="service_end_time"                data-endpoint="POSTapi-v1-subscriptions"
               value="2107-08-20"
               data-component="body">
    <br>
<p>This field is required when <code>schedule</code> is <code>custom</code>. Must be a valid date in the format <code>H:i</code>. Must be a date after <code>service_start_time</code>. Example: <code>2107-08-20</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>contract_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contract_type"                data-endpoint="POSTapi-v1-subscriptions"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>start_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="start_date"                data-endpoint="POSTapi-v1-subscriptions"
               value="2026-07-21T16:46:00"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-07-21T16:46:00</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>end_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="end_date"                data-endpoint="POSTapi-v1-subscriptions"
               value="2107-08-20"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after <code>start_date</code>. Example: <code>2107-08-20</code></p>
        </div>
        </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscriptions--subscription_id--status">PATCH api/v1/subscriptions/{subscription_id}/status</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-subscriptions--subscription_id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/subscriptions/17/status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"cancelled\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriptions/17/status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "cancelled"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-subscriptions--subscription_id--status">
</span>
<span id="execution-results-PATCHapi-v1-subscriptions--subscription_id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-subscriptions--subscription_id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-subscriptions--subscription_id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-subscriptions--subscription_id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-subscriptions--subscription_id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-subscriptions--subscription_id--status" data-method="PATCH"
      data-path="api/v1/subscriptions/{subscription_id}/status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-subscriptions--subscription_id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-subscriptions--subscription_id--status"
                    onclick="tryItOut('PATCHapi-v1-subscriptions--subscription_id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-subscriptions--subscription_id--status"
                    onclick="cancelTryOut('PATCHapi-v1-subscriptions--subscription_id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-subscriptions--subscription_id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/subscriptions/{subscription_id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-subscriptions--subscription_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-subscriptions--subscription_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subscription_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscription_id"                data-endpoint="PATCHapi-v1-subscriptions--subscription_id--status"
               value="17"
               data-component="url">
    <br>
<p>The ID of the subscription. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-subscriptions--subscription_id--status"
               value="cancelled"
               data-component="body">
    <br>
<p>Example: <code>cancelled</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>active</code></li> <li><code>suspended</code></li> <li><code>cancelled</code></li> <li><code>rejected</code></li></ul>
        </div>
        </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-GETapi-v1-subscriptions--subscription_id--contract-pdf">GET api/v1/subscriptions/{subscription_id}/contract-pdf</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-subscriptions--subscription_id--contract-pdf">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/subscriptions/17/contract-pdf" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscriptions/17/contract-pdf"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-subscriptions--subscription_id--contract-pdf">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-subscriptions--subscription_id--contract-pdf" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-subscriptions--subscription_id--contract-pdf"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-subscriptions--subscription_id--contract-pdf"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-subscriptions--subscription_id--contract-pdf" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-subscriptions--subscription_id--contract-pdf">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-subscriptions--subscription_id--contract-pdf" data-method="GET"
      data-path="api/v1/subscriptions/{subscription_id}/contract-pdf"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-subscriptions--subscription_id--contract-pdf', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-subscriptions--subscription_id--contract-pdf"
                    onclick="tryItOut('GETapi-v1-subscriptions--subscription_id--contract-pdf');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-subscriptions--subscription_id--contract-pdf"
                    onclick="cancelTryOut('GETapi-v1-subscriptions--subscription_id--contract-pdf');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-subscriptions--subscription_id--contract-pdf"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/subscriptions/{subscription_id}/contract-pdf</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-subscriptions--subscription_id--contract-pdf"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-subscriptions--subscription_id--contract-pdf"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subscription_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscription_id"                data-endpoint="GETapi-v1-subscriptions--subscription_id--contract-pdf"
               value="17"
               data-component="url">
    <br>
<p>The ID of the subscription. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-GETapi-v1-subscription-service-requests">GET api/v1/subscription-service-requests</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-subscription-service-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/subscription-service-requests" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscription-service-requests"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-subscription-service-requests">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-subscription-service-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-subscription-service-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-subscription-service-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-subscription-service-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-subscription-service-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-subscription-service-requests" data-method="GET"
      data-path="api/v1/subscription-service-requests"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-subscription-service-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-subscription-service-requests"
                    onclick="tryItOut('GETapi-v1-subscription-service-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-subscription-service-requests"
                    onclick="cancelTryOut('GETapi-v1-subscription-service-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-subscription-service-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/subscription-service-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-subscription-service-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-subscription-service-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-GETapi-v1-subscription-service-requests--serviceRequest_id-">GET api/v1/subscription-service-requests/{serviceRequest_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-subscription-service-requests--serviceRequest_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/subscription-service-requests/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscription-service-requests/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-subscription-service-requests--serviceRequest_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-subscription-service-requests--serviceRequest_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-subscription-service-requests--serviceRequest_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-subscription-service-requests--serviceRequest_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-subscription-service-requests--serviceRequest_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-subscription-service-requests--serviceRequest_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-subscription-service-requests--serviceRequest_id-" data-method="GET"
      data-path="api/v1/subscription-service-requests/{serviceRequest_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-subscription-service-requests--serviceRequest_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-subscription-service-requests--serviceRequest_id-"
                    onclick="tryItOut('GETapi-v1-subscription-service-requests--serviceRequest_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-subscription-service-requests--serviceRequest_id-"
                    onclick="cancelTryOut('GETapi-v1-subscription-service-requests--serviceRequest_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-subscription-service-requests--serviceRequest_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/subscription-service-requests/{serviceRequest_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-subscription-service-requests--serviceRequest_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-subscription-service-requests--serviceRequest_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>serviceRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="serviceRequest_id"                data-endpoint="GETapi-v1-subscription-service-requests--serviceRequest_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the serviceRequest. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-POSTapi-v1-subscription-service-requests">POST api/v1/subscription-service-requests</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-subscription-service-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/subscription-service-requests" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"subscription_id\": 17,
    \"request_type\": \"extra_hours\",
    \"event_type\": \"exam\",
    \"description\": \"Dolorum amet iste laborum eius est dolor.\",
    \"extra_capacity_kw\": 4,
    \"starts_at\": \"2107-08-20\",
    \"ends_at\": \"2107-08-20\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscription-service-requests"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "subscription_id": 17,
    "request_type": "extra_hours",
    "event_type": "exam",
    "description": "Dolorum amet iste laborum eius est dolor.",
    "extra_capacity_kw": 4,
    "starts_at": "2107-08-20",
    "ends_at": "2107-08-20"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-subscription-service-requests">
</span>
<span id="execution-results-POSTapi-v1-subscription-service-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-subscription-service-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-subscription-service-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-subscription-service-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-subscription-service-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-subscription-service-requests" data-method="POST"
      data-path="api/v1/subscription-service-requests"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-subscription-service-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-subscription-service-requests"
                    onclick="tryItOut('POSTapi-v1-subscription-service-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-subscription-service-requests"
                    onclick="cancelTryOut('POSTapi-v1-subscription-service-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-subscription-service-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/subscription-service-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subscription_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscription_id"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>request_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="request_type"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="extra_hours"
               data-component="body">
    <br>
<p>Example: <code>extra_hours</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>event</code></li> <li><code>extra_capacity</code></li> <li><code>extra_hours</code></li> <li><code>maintenance</code></li> <li><code>other</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>event_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="event_type"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="exam"
               data-component="body">
    <br>
<p>Example: <code>exam</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>wedding</code></li> <li><code>exam</code></li> <li><code>religious_event</code></li> <li><code>family_event</code></li> <li><code>other</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="Dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>Dolorum amet iste laborum eius est dolor.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>extra_capacity_kw</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="extra_capacity_kw"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="4"
               data-component="body">
    <br>
<p>Must be at least 0.1. Must not be greater than 9999.99. Example: <code>4</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>starts_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="starts_at"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="2107-08-20"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after <code>now</code>. Example: <code>2107-08-20</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ends_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ends_at"                data-endpoint="POSTapi-v1-subscription-service-requests"
               value="2107-08-20"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after <code>starts_at</code>. Example: <code>2107-08-20</code></p>
        </div>
        </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review">PATCH api/v1/subscription-service-requests/{serviceRequest_id}/review</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/subscription-service-requests/17/review" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"decision\": \"approved\",
    \"review_note\": \"vmqeopfuudtdsufvyvddq\",
    \"fee_amount\": 1,
    \"fee_currency\": \"ILS\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscription-service-requests/17/review"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "decision": "approved",
    "review_note": "vmqeopfuudtdsufvyvddq",
    "fee_amount": 1,
    "fee_currency": "ILS"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review">
</span>
<span id="execution-results-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review" data-method="PATCH"
      data-path="api/v1/subscription-service-requests/{serviceRequest_id}/review"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-subscription-service-requests--serviceRequest_id--review', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
                    onclick="tryItOut('PATCHapi-v1-subscription-service-requests--serviceRequest_id--review');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
                    onclick="cancelTryOut('PATCHapi-v1-subscription-service-requests--serviceRequest_id--review');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/subscription-service-requests/{serviceRequest_id}/review</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>serviceRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="serviceRequest_id"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
               value="17"
               data-component="url">
    <br>
<p>The ID of the serviceRequest. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>decision</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="decision"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
               value="approved"
               data-component="body">
    <br>
<p>Example: <code>approved</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>approved</code></li> <li><code>rejected</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>review_note</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="review_note"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>fee_amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="fee_amount"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
               value="1"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>fee_currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="fee_currency"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--review"
               value="ILS"
               data-component="body">
    <br>
<p>This field is required when <code>fee_amount</code> is present. Example: <code>ILS</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ILS</code></li> <li><code>USD</code></li></ul>
        </div>
        </form>

                    <h2 id="alashtrakat-otlbat-alkhdm-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel">PATCH api/v1/subscription-service-requests/{serviceRequest_id}/cancel</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/subscription-service-requests/17/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscription-service-requests/17/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel">
</span>
<span id="execution-results-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel" data-method="PATCH"
      data-path="api/v1/subscription-service-requests/{serviceRequest_id}/cancel"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel"
                    onclick="tryItOut('PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel"
                    onclick="cancelTryOut('PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/subscription-service-requests/{serviceRequest_id}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>serviceRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="serviceRequest_id"                data-endpoint="PATCHapi-v1-subscription-service-requests--serviceRequest_id--cancel"
               value="17"
               data-component="url">
    <br>
<p>The ID of the serviceRequest. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="aldfaaat-oosayl-aldfaa">الدفعات ووسائل الدفع</h1>

    

                                <h2 id="aldfaaat-oosayl-aldfaa-GETapi-v1-payments">GET api/v1/payments</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-payments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/payments" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-payments">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-payments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-payments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-payments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-payments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-payments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-payments" data-method="GET"
      data-path="api/v1/payments"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-payments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-payments"
                    onclick="tryItOut('GETapi-v1-payments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-payments"
                    onclick="cancelTryOut('GETapi-v1-payments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-payments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/payments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-payments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-payments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-GETapi-v1-payments-export">GET api/v1/payments/export</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-payments-export">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/payments/export" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments/export"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-payments-export">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-payments-export" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-payments-export"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-payments-export"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-payments-export" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-payments-export">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-payments-export" data-method="GET"
      data-path="api/v1/payments/export"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-payments-export', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-payments-export"
                    onclick="tryItOut('GETapi-v1-payments-export');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-payments-export"
                    onclick="cancelTryOut('GETapi-v1-payments-export');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-payments-export"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/payments/export</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-payments-export"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-payments-export"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-GETapi-v1-payments--payment_id-">GET api/v1/payments/{payment_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-payments--payment_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/payments/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-payments--payment_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-payments--payment_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-payments--payment_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-payments--payment_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-payments--payment_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-payments--payment_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-payments--payment_id-" data-method="GET"
      data-path="api/v1/payments/{payment_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-payments--payment_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-payments--payment_id-"
                    onclick="tryItOut('GETapi-v1-payments--payment_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-payments--payment_id-"
                    onclick="cancelTryOut('GETapi-v1-payments--payment_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-payments--payment_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/payments/{payment_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-payments--payment_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-payments--payment_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_id"                data-endpoint="GETapi-v1-payments--payment_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the payment. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-POSTapi-v1-payments">POST api/v1/payments</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-payments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/payments" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "invoice_id=17"\
    --form "amount=45"\
    --form "payment_method_id=17"\
    --form "transaction_reference=mqeopfuudtdsufvyvddqa"\
    --form "note=mniihfqcoynlazghdtqtq"\
    --form "override_reason=xbajwbpilpmufinllwloa"\
    --form "currency=USD"\
    --form "attachments[]=@C:\Users\Aya\AppData\Local\Temp\php6E58.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('invoice_id', '17');
body.append('amount', '45');
body.append('payment_method_id', '17');
body.append('transaction_reference', 'mqeopfuudtdsufvyvddqa');
body.append('note', 'mniihfqcoynlazghdtqtq');
body.append('override_reason', 'xbajwbpilpmufinllwloa');
body.append('currency', 'USD');
body.append('attachments[]', document.querySelector('input[name="attachments[]"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-payments">
</span>
<span id="execution-results-POSTapi-v1-payments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-payments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-payments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-payments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-payments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-payments" data-method="POST"
      data-path="api/v1/payments"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-payments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-payments"
                    onclick="tryItOut('POSTapi-v1-payments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-payments"
                    onclick="cancelTryOut('POSTapi-v1-payments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-payments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/payments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-payments"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-payments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>invoice_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="invoice_id"                data-endpoint="POSTapi-v1-payments"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="amount"                data-endpoint="POSTapi-v1-payments"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 0.01. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>payment_method_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_method_id"                data-endpoint="POSTapi-v1-payments"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>attachments</code></b>&nbsp;&nbsp;
<small>file[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="attachments[0]"                data-endpoint="POSTapi-v1-payments"
               data-component="body">
        <input type="file" style="display: none"
               name="attachments[1]"                data-endpoint="POSTapi-v1-payments"
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 5120 kilobytes.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>transaction_reference</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="transaction_reference"                data-endpoint="POSTapi-v1-payments"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>note</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="note"                data-endpoint="POSTapi-v1-payments"
               value="mniihfqcoynlazghdtqtq"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>mniihfqcoynlazghdtqtq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>override_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="override_reason"                data-endpoint="POSTapi-v1-payments"
               value="xbajwbpilpmufinllwloa"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>xbajwbpilpmufinllwloa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-payments"
               value="USD"
               data-component="body">
    <br>
<p>Example: <code>USD</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ILS</code></li> <li><code>USD</code></li></ul>
        </div>
        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--approve">اعتماد دفعة Pending/NeedsCorrection → Paid.</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-payments--payment_id--approve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/payments/17/approve" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments/17/approve"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-payments--payment_id--approve">
</span>
<span id="execution-results-PATCHapi-v1-payments--payment_id--approve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-payments--payment_id--approve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-payments--payment_id--approve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-payments--payment_id--approve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-payments--payment_id--approve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-payments--payment_id--approve" data-method="PATCH"
      data-path="api/v1/payments/{payment_id}/approve"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-payments--payment_id--approve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-payments--payment_id--approve"
                    onclick="tryItOut('PATCHapi-v1-payments--payment_id--approve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-payments--payment_id--approve"
                    onclick="cancelTryOut('PATCHapi-v1-payments--payment_id--approve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-payments--payment_id--approve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/payments/{payment_id}/approve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-payments--payment_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-payments--payment_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_id"                data-endpoint="PATCHapi-v1-payments--payment_id--approve"
               value="17"
               data-component="url">
    <br>
<p>The ID of the payment. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--reject">PATCH api/v1/payments/{payment_id}/reject</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-payments--payment_id--reject">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/payments/17/reject" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments/17/reject"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-payments--payment_id--reject">
</span>
<span id="execution-results-PATCHapi-v1-payments--payment_id--reject" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-payments--payment_id--reject"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-payments--payment_id--reject"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-payments--payment_id--reject" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-payments--payment_id--reject">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-payments--payment_id--reject" data-method="PATCH"
      data-path="api/v1/payments/{payment_id}/reject"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-payments--payment_id--reject', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-payments--payment_id--reject"
                    onclick="tryItOut('PATCHapi-v1-payments--payment_id--reject');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-payments--payment_id--reject"
                    onclick="cancelTryOut('PATCHapi-v1-payments--payment_id--reject');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-payments--payment_id--reject"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/payments/{payment_id}/reject</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-payments--payment_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-payments--payment_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_id"                data-endpoint="PATCHapi-v1-payments--payment_id--reject"
               value="17"
               data-component="url">
    <br>
<p>The ID of the payment. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="PATCHapi-v1-payments--payment_id--reject"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--needs-correction">PATCH api/v1/payments/{payment_id}/needs-correction</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-payments--payment_id--needs-correction">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/payments/17/needs-correction" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"note\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments/17/needs-correction"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "note": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-payments--payment_id--needs-correction">
</span>
<span id="execution-results-PATCHapi-v1-payments--payment_id--needs-correction" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-payments--payment_id--needs-correction"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-payments--payment_id--needs-correction"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-payments--payment_id--needs-correction" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-payments--payment_id--needs-correction">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-payments--payment_id--needs-correction" data-method="PATCH"
      data-path="api/v1/payments/{payment_id}/needs-correction"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-payments--payment_id--needs-correction', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-payments--payment_id--needs-correction"
                    onclick="tryItOut('PATCHapi-v1-payments--payment_id--needs-correction');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-payments--payment_id--needs-correction"
                    onclick="cancelTryOut('PATCHapi-v1-payments--payment_id--needs-correction');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-payments--payment_id--needs-correction"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/payments/{payment_id}/needs-correction</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-payments--payment_id--needs-correction"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-payments--payment_id--needs-correction"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_id"                data-endpoint="PATCHapi-v1-payments--payment_id--needs-correction"
               value="17"
               data-component="url">
    <br>
<p>The ID of the payment. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>note</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="note"                data-endpoint="PATCHapi-v1-payments--payment_id--needs-correction"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--resubmit">PATCH api/v1/payments/{payment_id}/resubmit</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-payments--payment_id--resubmit">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/payments/17/resubmit" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "amount=73"\
    --form "transaction_reference=mqeopfuudtdsufvyvddqa"\
    --form "note=mniihfqcoynlazghdtqtq"\
    --form "attachments[]=@C:\Users\Aya\AppData\Local\Temp\php6E68.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments/17/resubmit"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('amount', '73');
body.append('transaction_reference', 'mqeopfuudtdsufvyvddqa');
body.append('note', 'mniihfqcoynlazghdtqtq');
body.append('attachments[]', document.querySelector('input[name="attachments[]"]').files[0]);

fetch(url, {
    method: "PATCH",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-payments--payment_id--resubmit">
</span>
<span id="execution-results-PATCHapi-v1-payments--payment_id--resubmit" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-payments--payment_id--resubmit"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-payments--payment_id--resubmit"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-payments--payment_id--resubmit" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-payments--payment_id--resubmit">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-payments--payment_id--resubmit" data-method="PATCH"
      data-path="api/v1/payments/{payment_id}/resubmit"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-payments--payment_id--resubmit', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-payments--payment_id--resubmit"
                    onclick="tryItOut('PATCHapi-v1-payments--payment_id--resubmit');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-payments--payment_id--resubmit"
                    onclick="cancelTryOut('PATCHapi-v1-payments--payment_id--resubmit');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-payments--payment_id--resubmit"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/payments/{payment_id}/resubmit</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-payments--payment_id--resubmit"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-payments--payment_id--resubmit"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_id"                data-endpoint="PATCHapi-v1-payments--payment_id--resubmit"
               value="17"
               data-component="url">
    <br>
<p>The ID of the payment. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="amount"                data-endpoint="PATCHapi-v1-payments--payment_id--resubmit"
               value="73"
               data-component="body">
    <br>
<p>@var Payment $payment Must be at least 0.01. Example: <code>73</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>transaction_reference</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="transaction_reference"                data-endpoint="PATCHapi-v1-payments--payment_id--resubmit"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>note</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="note"                data-endpoint="PATCHapi-v1-payments--payment_id--resubmit"
               value="mniihfqcoynlazghdtqtq"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>mniihfqcoynlazghdtqtq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>attachments</code></b>&nbsp;&nbsp;
<small>file[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="attachments[0]"                data-endpoint="PATCHapi-v1-payments--payment_id--resubmit"
               data-component="body">
        <input type="file" style="display: none"
               name="attachments[1]"                data-endpoint="PATCHapi-v1-payments--payment_id--resubmit"
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 5120 kilobytes.</p>
        </div>
        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-PATCHapi-v1-payments--payment_id--cancel">PATCH api/v1/payments/{payment_id}/cancel</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-payments--payment_id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/payments/17/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payments/17/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-payments--payment_id--cancel">
</span>
<span id="execution-results-PATCHapi-v1-payments--payment_id--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-payments--payment_id--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-payments--payment_id--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-payments--payment_id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-payments--payment_id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-payments--payment_id--cancel" data-method="PATCH"
      data-path="api/v1/payments/{payment_id}/cancel"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-payments--payment_id--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-payments--payment_id--cancel"
                    onclick="tryItOut('PATCHapi-v1-payments--payment_id--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-payments--payment_id--cancel"
                    onclick="cancelTryOut('PATCHapi-v1-payments--payment_id--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-payments--payment_id--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/payments/{payment_id}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-payments--payment_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-payments--payment_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_id"                data-endpoint="PATCHapi-v1-payments--payment_id--cancel"
               value="17"
               data-component="url">
    <br>
<p>The ID of the payment. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-GETapi-v1-payment-methods">GET api/v1/payment-methods</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-payment-methods">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/payment-methods" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payment-methods"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-payment-methods">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-payment-methods" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-payment-methods"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-payment-methods"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-payment-methods" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-payment-methods">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-payment-methods" data-method="GET"
      data-path="api/v1/payment-methods"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-payment-methods', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-payment-methods"
                    onclick="tryItOut('GETapi-v1-payment-methods');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-payment-methods"
                    onclick="cancelTryOut('GETapi-v1-payment-methods');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-payment-methods"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/payment-methods</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-payment-methods"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-payment-methods"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-POSTapi-v1-payment-methods">POST api/v1/payment-methods</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-payment-methods">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/payment-methods" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"type\": \"bank\",
    \"is_default\": false,
    \"currency\": \"ILS\",
    \"bank_name\": \"vmqeopfuudtdsufvyvddq\",
    \"account_name\": \"amniihfqcoynlazghdtqt\",
    \"account_number\": \"qxbajwbpilpmufinllwlo\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payment-methods"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "type": "bank",
    "is_default": false,
    "currency": "ILS",
    "bank_name": "vmqeopfuudtdsufvyvddq",
    "account_name": "amniihfqcoynlazghdtqt",
    "account_number": "qxbajwbpilpmufinllwlo"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-payment-methods">
</span>
<span id="execution-results-POSTapi-v1-payment-methods" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-payment-methods"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-payment-methods"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-payment-methods" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-payment-methods">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-payment-methods" data-method="POST"
      data-path="api/v1/payment-methods"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-payment-methods', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-payment-methods"
                    onclick="tryItOut('POSTapi-v1-payment-methods');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-payment-methods"
                    onclick="cancelTryOut('POSTapi-v1-payment-methods');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-payment-methods"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/payment-methods</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-payment-methods"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-payment-methods"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-v1-payment-methods"
               value="bank"
               data-component="body">
    <br>
<p>Example: <code>bank</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>bank</code></li> <li><code>wallet</code></li> <li><code>cash</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_default</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-payment-methods" style="display: none">
            <input type="radio" name="is_default"
                   value="true"
                   data-endpoint="POSTapi-v1-payment-methods"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-payment-methods" style="display: none">
            <input type="radio" name="is_default"
                   value="false"
                   data-endpoint="POSTapi-v1-payment-methods"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-payment-methods"
               value="ILS"
               data-component="body">
    <br>
<p>Example: <code>ILS</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ILS</code></li> <li><code>USD</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>bank_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="bank_name"                data-endpoint="POSTapi-v1-payment-methods"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>account_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="account_name"                data-endpoint="POSTapi-v1-payment-methods"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>account_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="account_number"                data-endpoint="POSTapi-v1-payment-methods"
               value="qxbajwbpilpmufinllwlo"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>qxbajwbpilpmufinllwlo</code></p>
        </div>
        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-PUTapi-v1-payment-methods--payment_method_id-">PUT api/v1/payment-methods/{payment_method_id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-payment-methods--payment_method_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/v1/payment-methods/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"type\": \"wallet\",
    \"is_default\": false,
    \"currency\": \"ILS\",
    \"bank_name\": \"vmqeopfuudtdsufvyvddq\",
    \"account_name\": \"amniihfqcoynlazghdtqt\",
    \"account_number\": \"qxbajwbpilpmufinllwlo\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payment-methods/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "type": "wallet",
    "is_default": false,
    "currency": "ILS",
    "bank_name": "vmqeopfuudtdsufvyvddq",
    "account_name": "amniihfqcoynlazghdtqt",
    "account_number": "qxbajwbpilpmufinllwlo"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-payment-methods--payment_method_id-">
</span>
<span id="execution-results-PUTapi-v1-payment-methods--payment_method_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-payment-methods--payment_method_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-payment-methods--payment_method_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-payment-methods--payment_method_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-payment-methods--payment_method_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-payment-methods--payment_method_id-" data-method="PUT"
      data-path="api/v1/payment-methods/{payment_method_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-payment-methods--payment_method_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-payment-methods--payment_method_id-"
                    onclick="tryItOut('PUTapi-v1-payment-methods--payment_method_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-payment-methods--payment_method_id-"
                    onclick="cancelTryOut('PUTapi-v1-payment-methods--payment_method_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-payment-methods--payment_method_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/payment-methods/{payment_method_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payment_method_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_method_id"                data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the payment method. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
               value="wallet"
               data-component="body">
    <br>
<p>Example: <code>wallet</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>bank</code></li> <li><code>wallet</code></li> <li><code>cash</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_default</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-payment-methods--payment_method_id-" style="display: none">
            <input type="radio" name="is_default"
                   value="true"
                   data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-payment-methods--payment_method_id-" style="display: none">
            <input type="radio" name="is_default"
                   value="false"
                   data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
               value="ILS"
               data-component="body">
    <br>
<p>Example: <code>ILS</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ILS</code></li> <li><code>USD</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>bank_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="bank_name"                data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>account_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="account_name"                data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>account_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="account_number"                data-endpoint="PUTapi-v1-payment-methods--payment_method_id-"
               value="qxbajwbpilpmufinllwlo"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>qxbajwbpilpmufinllwlo</code></p>
        </div>
        </form>

                    <h2 id="aldfaaat-oosayl-aldfaa-DELETEapi-v1-payment-methods--payment_method_id-">DELETE api/v1/payment-methods/{payment_method_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-payment-methods--payment_method_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/payment-methods/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payment-methods/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-payment-methods--payment_method_id-">
</span>
<span id="execution-results-DELETEapi-v1-payment-methods--payment_method_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-payment-methods--payment_method_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-payment-methods--payment_method_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-payment-methods--payment_method_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-payment-methods--payment_method_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-payment-methods--payment_method_id-" data-method="DELETE"
      data-path="api/v1/payment-methods/{payment_method_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-payment-methods--payment_method_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-payment-methods--payment_method_id-"
                    onclick="tryItOut('DELETEapi-v1-payment-methods--payment_method_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-payment-methods--payment_method_id-"
                    onclick="cancelTryOut('DELETEapi-v1-payment-methods--payment_method_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-payment-methods--payment_method_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/payment-methods/{payment_method_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-payment-methods--payment_method_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-payment-methods--payment_method_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payment_method_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payment_method_id"                data-endpoint="DELETEapi-v1-payment-methods--payment_method_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the payment method. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="alshkao">الشكاوى</h1>

    

                                <h2 id="alshkao-GETapi-v1-complaints">GET api/v1/complaints</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-complaints">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/complaints" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/complaints"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-complaints">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-complaints" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-complaints"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-complaints"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-complaints" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-complaints">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-complaints" data-method="GET"
      data-path="api/v1/complaints"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-complaints', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-complaints"
                    onclick="tryItOut('GETapi-v1-complaints');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-complaints"
                    onclick="cancelTryOut('GETapi-v1-complaints');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-complaints"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/complaints</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-complaints"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-complaints"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alshkao-POSTapi-v1-complaints">POST api/v1/complaints</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-complaints">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/complaints" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"complainable_type\": \"payment\",
    \"complainable_id\": 17,
    \"subject\": \"mqeopfuudtdsufvyvddqa\",
    \"description\": \"Molestias ipsam sit veniam sed fuga aspernatur.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/complaints"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "complainable_type": "payment",
    "complainable_id": 17,
    "subject": "mqeopfuudtdsufvyvddqa",
    "description": "Molestias ipsam sit veniam sed fuga aspernatur."
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-complaints">
</span>
<span id="execution-results-POSTapi-v1-complaints" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-complaints"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-complaints"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-complaints" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-complaints">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-complaints" data-method="POST"
      data-path="api/v1/complaints"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-complaints', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-complaints"
                    onclick="tryItOut('POSTapi-v1-complaints');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-complaints"
                    onclick="cancelTryOut('POSTapi-v1-complaints');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-complaints"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/complaints</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-complaints"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-complaints"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>complainable_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="complainable_type"                data-endpoint="POSTapi-v1-complaints"
               value="payment"
               data-component="body">
    <br>
<p>Example: <code>payment</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>invoice</code></li> <li><code>generator</code></li> <li><code>fault</code></li> <li><code>user</code></li> <li><code>subscription</code></li> <li><code>payment</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>complainable_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="complainable_id"                data-endpoint="POSTapi-v1-complaints"
               value="17"
               data-component="body">
    <br>
<p>This field is required when <code>complainable_type</code> is present. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subject</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subject"                data-endpoint="POSTapi-v1-complaints"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-complaints"
               value="Molestias ipsam sit veniam sed fuga aspernatur."
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>Molestias ipsam sit veniam sed fuga aspernatur.</code></p>
        </div>
        </form>

                    <h2 id="alshkao-GETapi-v1-complaints--id-">GET api/v1/complaints/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-complaints--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/complaints/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/complaints/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-complaints--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-complaints--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-complaints--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-complaints--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-complaints--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-complaints--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-complaints--id-" data-method="GET"
      data-path="api/v1/complaints/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-complaints--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-complaints--id-"
                    onclick="tryItOut('GETapi-v1-complaints--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-complaints--id-"
                    onclick="cancelTryOut('GETapi-v1-complaints--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-complaints--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/complaints/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-complaints--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-complaints--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-complaints--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the complaint. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alshkao-PATCHapi-v1-complaints--complaint_id--status">PATCH api/v1/complaints/{complaint_id}/status</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-complaints--complaint_id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/complaints/17/status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"resolved\",
    \"resolution_note\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/complaints/17/status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "resolved",
    "resolution_note": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-complaints--complaint_id--status">
</span>
<span id="execution-results-PATCHapi-v1-complaints--complaint_id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-complaints--complaint_id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-complaints--complaint_id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-complaints--complaint_id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-complaints--complaint_id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-complaints--complaint_id--status" data-method="PATCH"
      data-path="api/v1/complaints/{complaint_id}/status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-complaints--complaint_id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-complaints--complaint_id--status"
                    onclick="tryItOut('PATCHapi-v1-complaints--complaint_id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-complaints--complaint_id--status"
                    onclick="cancelTryOut('PATCHapi-v1-complaints--complaint_id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-complaints--complaint_id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/complaints/{complaint_id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-complaints--complaint_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-complaints--complaint_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>complaint_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="complaint_id"                data-endpoint="PATCHapi-v1-complaints--complaint_id--status"
               value="17"
               data-component="url">
    <br>
<p>The ID of the complaint. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-complaints--complaint_id--status"
               value="resolved"
               data-component="body">
    <br>
<p>Example: <code>resolved</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>in_progress</code></li> <li><code>resolved</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>resolution_note</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="resolution_note"                data-endpoint="PATCHapi-v1-complaints--complaint_id--status"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>This field is required when <code>status</code> is <code>resolved</code>. Must not be greater than 2000 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                    <h2 id="alshkao-DELETEapi-v1-complaints--id-">DELETE api/v1/complaints/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-complaints--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/complaints/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/complaints/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-complaints--id-">
</span>
<span id="execution-results-DELETEapi-v1-complaints--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-complaints--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-complaints--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-complaints--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-complaints--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-complaints--id-" data-method="DELETE"
      data-path="api/v1/complaints/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-complaints--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-complaints--id-"
                    onclick="tryItOut('DELETEapi-v1-complaints--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-complaints--id-"
                    onclick="cancelTryOut('DELETEapi-v1-complaints--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-complaints--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/complaints/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-complaints--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-complaints--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-complaints--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the complaint. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="alaarod-oalkhsomat">العروض والخصومات</h1>

    

                                <h2 id="alaarod-oalkhsomat-GETapi-v1-offers">GET api/v1/offers</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-offers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/offers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/offers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-offers">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-offers" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-offers"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-offers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-offers" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-offers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-offers" data-method="GET"
      data-path="api/v1/offers"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-offers', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-offers"
                    onclick="tryItOut('GETapi-v1-offers');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-offers"
                    onclick="cancelTryOut('GETapi-v1-offers');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-offers"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/offers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-offers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-offers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alaarod-oalkhsomat-POSTapi-v1-offers">POST api/v1/offers</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-offers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/offers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": \"vmqeopfuudtdsufvyvddq\",
    \"description\": \"Dolores molestias ipsam sit.\",
    \"discount_type\": \"fixed\",
    \"discount_value\": 25,
    \"target_mode\": \"selected\",
    \"beneficiary_type\": \"normal\",
    \"subscriber_ids\": [
        17
    ],
    \"start_date\": \"2107-08-20\",
    \"end_date\": \"2107-08-20\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/offers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": "vmqeopfuudtdsufvyvddq",
    "description": "Dolores molestias ipsam sit.",
    "discount_type": "fixed",
    "discount_value": 25,
    "target_mode": "selected",
    "beneficiary_type": "normal",
    "subscriber_ids": [
        17
    ],
    "start_date": "2107-08-20",
    "end_date": "2107-08-20"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-offers">
</span>
<span id="execution-results-POSTapi-v1-offers" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-offers"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-offers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-offers" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-offers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-offers" data-method="POST"
      data-path="api/v1/offers"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-offers', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-offers"
                    onclick="tryItOut('POSTapi-v1-offers');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-offers"
                    onclick="cancelTryOut('POSTapi-v1-offers');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-offers"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/offers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-offers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-offers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-v1-offers"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-offers"
               value="Dolores molestias ipsam sit."
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>Dolores molestias ipsam sit.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>discount_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="discount_type"                data-endpoint="POSTapi-v1-offers"
               value="fixed"
               data-component="body">
    <br>
<p>Example: <code>fixed</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>percentage</code></li> <li><code>fixed</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>discount_value</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="discount_value"                data-endpoint="POSTapi-v1-offers"
               value="25"
               data-component="body">
    <br>
<p>Must be at least 0.01. Example: <code>25</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>target_mode</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="target_mode"                data-endpoint="POSTapi-v1-offers"
               value="selected"
               data-component="body">
    <br>
<p>Example: <code>selected</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>all</code></li> <li><code>beneficiary</code></li> <li><code>selected</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>beneficiary_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="beneficiary_type"                data-endpoint="POSTapi-v1-offers"
               value="normal"
               data-component="body">
    <br>
<p>Example: <code>normal</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>normal</code></li> <li><code>special</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subscriber_ids</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscriber_ids[0]"                data-endpoint="POSTapi-v1-offers"
               data-component="body">
        <input type="number" style="display: none"
               name="subscriber_ids[1]"                data-endpoint="POSTapi-v1-offers"
               data-component="body">
    <br>
<p>Must match an existing stored value.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>start_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="start_date"                data-endpoint="POSTapi-v1-offers"
               value="2107-08-20"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>today</code>. Example: <code>2107-08-20</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>end_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="end_date"                data-endpoint="POSTapi-v1-offers"
               value="2107-08-20"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after <code>start_date</code>. Example: <code>2107-08-20</code></p>
        </div>
        </form>

                    <h2 id="alaarod-oalkhsomat-GETapi-v1-offers--id-">GET api/v1/offers/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-offers--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/offers/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/offers/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-offers--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-offers--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-offers--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-offers--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-offers--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-offers--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-offers--id-" data-method="GET"
      data-path="api/v1/offers/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-offers--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-offers--id-"
                    onclick="tryItOut('GETapi-v1-offers--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-offers--id-"
                    onclick="cancelTryOut('GETapi-v1-offers--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-offers--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/offers/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-offers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-offers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-offers--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the offer. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alaarod-oalkhsomat-PATCHapi-v1-offers--id-">PATCH api/v1/offers/{id}</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-offers--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/offers/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": \"vmqeopfuudtdsufvyvddq\",
    \"description\": \"Dolores molestias ipsam sit.\",
    \"discount_value\": 25,
    \"start_date\": \"2026-07-21T16:46:01\",
    \"end_date\": \"2107-08-20\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/offers/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": "vmqeopfuudtdsufvyvddq",
    "description": "Dolores molestias ipsam sit.",
    "discount_value": 25,
    "start_date": "2026-07-21T16:46:01",
    "end_date": "2107-08-20"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-offers--id-">
</span>
<span id="execution-results-PATCHapi-v1-offers--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-offers--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-offers--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-offers--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-offers--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-offers--id-" data-method="PATCH"
      data-path="api/v1/offers/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-offers--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-offers--id-"
                    onclick="tryItOut('PATCHapi-v1-offers--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-offers--id-"
                    onclick="cancelTryOut('PATCHapi-v1-offers--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-offers--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/offers/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-offers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-offers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PATCHapi-v1-offers--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the offer. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="PATCHapi-v1-offers--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 191 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PATCHapi-v1-offers--id-"
               value="Dolores molestias ipsam sit."
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>Dolores molestias ipsam sit.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>discount_value</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="discount_value"                data-endpoint="PATCHapi-v1-offers--id-"
               value="25"
               data-component="body">
    <br>
<p>Must be at least 0.01. Example: <code>25</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>start_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="start_date"                data-endpoint="PATCHapi-v1-offers--id-"
               value="2026-07-21T16:46:01"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-07-21T16:46:01</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>end_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="end_date"                data-endpoint="PATCHapi-v1-offers--id-"
               value="2107-08-20"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after <code>start_date</code>. Example: <code>2107-08-20</code></p>
        </div>
        </form>

                    <h2 id="alaarod-oalkhsomat-PATCHapi-v1-offers--offer_id--cancel">PATCH api/v1/offers/{offer_id}/cancel</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-offers--offer_id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/offers/17/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/offers/17/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-offers--offer_id--cancel">
</span>
<span id="execution-results-PATCHapi-v1-offers--offer_id--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-offers--offer_id--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-offers--offer_id--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-offers--offer_id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-offers--offer_id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-offers--offer_id--cancel" data-method="PATCH"
      data-path="api/v1/offers/{offer_id}/cancel"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-offers--offer_id--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-offers--offer_id--cancel"
                    onclick="tryItOut('PATCHapi-v1-offers--offer_id--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-offers--offer_id--cancel"
                    onclick="cancelTryOut('PATCHapi-v1-offers--offer_id--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-offers--offer_id--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/offers/{offer_id}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-offers--offer_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-offers--offer_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>offer_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="offer_id"                data-endpoint="PATCHapi-v1-offers--offer_id--cancel"
               value="17"
               data-component="url">
    <br>
<p>The ID of the offer. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alaarod-oalkhsomat-DELETEapi-v1-offers--id-">DELETE api/v1/offers/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-offers--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/offers/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/offers/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-offers--id-">
</span>
<span id="execution-results-DELETEapi-v1-offers--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-offers--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-offers--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-offers--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-offers--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-offers--id-" data-method="DELETE"
      data-path="api/v1/offers/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-offers--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-offers--id-"
                    onclick="tryItOut('DELETEapi-v1-offers--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-offers--id-"
                    onclick="cancelTryOut('DELETEapi-v1-offers--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-offers--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/offers/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-offers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-offers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-offers--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the offer. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="alfnyyn-oaoamr-alshghl">الفنيين وأوامر الشغل</h1>

    

                                <h2 id="alfnyyn-oaoamr-alshghl-GETapi-v1-technicians">GET api/v1/technicians</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-technicians">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/technicians" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technicians"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-technicians">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-technicians" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-technicians"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-technicians"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-technicians" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-technicians">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-technicians" data-method="GET"
      data-path="api/v1/technicians"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-technicians', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-technicians"
                    onclick="tryItOut('GETapi-v1-technicians');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-technicians"
                    onclick="cancelTryOut('GETapi-v1-technicians');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-technicians"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/technicians</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-technicians"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-technicians"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-GETapi-v1-technicians--technician_id-">GET api/v1/technicians/{technician_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-technicians--technician_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/technicians/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technicians/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-technicians--technician_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-technicians--technician_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-technicians--technician_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-technicians--technician_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-technicians--technician_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-technicians--technician_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-technicians--technician_id-" data-method="GET"
      data-path="api/v1/technicians/{technician_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-technicians--technician_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-technicians--technician_id-"
                    onclick="tryItOut('GETapi-v1-technicians--technician_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-technicians--technician_id-"
                    onclick="cancelTryOut('GETapi-v1-technicians--technician_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-technicians--technician_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/technicians/{technician_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-technicians--technician_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-technicians--technician_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_id"                data-endpoint="GETapi-v1-technicians--technician_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-POSTapi-v1-technicians">POST api/v1/technicians</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-technicians">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/technicians" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"user_id\": 17,
    \"type\": \"private\",
    \"notes\": \"mqeopfuudtdsufvyvddqa\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technicians"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "user_id": 17,
    "type": "private",
    "notes": "mqeopfuudtdsufvyvddqa"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-technicians">
</span>
<span id="execution-results-POSTapi-v1-technicians" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-technicians"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-technicians"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-technicians" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-technicians">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-technicians" data-method="POST"
      data-path="api/v1/technicians"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-technicians', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-technicians"
                    onclick="tryItOut('POSTapi-v1-technicians');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-technicians"
                    onclick="cancelTryOut('POSTapi-v1-technicians');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-technicians"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/technicians</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-technicians"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-technicians"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="POSTapi-v1-technicians"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-v1-technicians"
               value="private"
               data-component="body">
    <br>
<p>Example: <code>private</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>private</code></li> <li><code>platform</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="POSTapi-v1-technicians"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technicians--technician_id-">PATCH api/v1/technicians/{technician_id}</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-technicians--technician_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/technicians/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"active\",
    \"notes\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technicians/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "active",
    "notes": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-technicians--technician_id-">
</span>
<span id="execution-results-PATCHapi-v1-technicians--technician_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-technicians--technician_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-technicians--technician_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-technicians--technician_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-technicians--technician_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-technicians--technician_id-" data-method="PATCH"
      data-path="api/v1/technicians/{technician_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-technicians--technician_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-technicians--technician_id-"
                    onclick="tryItOut('PATCHapi-v1-technicians--technician_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-technicians--technician_id-"
                    onclick="cancelTryOut('PATCHapi-v1-technicians--technician_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-technicians--technician_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/technicians/{technician_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-technicians--technician_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-technicians--technician_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_id"                data-endpoint="PATCHapi-v1-technicians--technician_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-technicians--technician_id-"
               value="active"
               data-component="body">
    <br>
<p>Example: <code>active</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>active</code></li> <li><code>inactive</code></li> <li><code>suspended</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="PATCHapi-v1-technicians--technician_id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-DELETEapi-v1-technicians--technician_id-">DELETE api/v1/technicians/{technician_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-technicians--technician_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/technicians/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technicians/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-technicians--technician_id-">
</span>
<span id="execution-results-DELETEapi-v1-technicians--technician_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-technicians--technician_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-technicians--technician_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-technicians--technician_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-technicians--technician_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-technicians--technician_id-" data-method="DELETE"
      data-path="api/v1/technicians/{technician_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-technicians--technician_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-technicians--technician_id-"
                    onclick="tryItOut('DELETEapi-v1-technicians--technician_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-technicians--technician_id-"
                    onclick="cancelTryOut('DELETEapi-v1-technicians--technician_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-technicians--technician_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/technicians/{technician_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-technicians--technician_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-technicians--technician_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_id"                data-endpoint="DELETEapi-v1-technicians--technician_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-GETapi-v1-technicians--technician_id--attachments">GET api/v1/technicians/{technician_id}/attachments</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-technicians--technician_id--attachments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/technicians/17/attachments" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technicians/17/attachments"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-technicians--technician_id--attachments">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-technicians--technician_id--attachments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-technicians--technician_id--attachments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-technicians--technician_id--attachments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-technicians--technician_id--attachments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-technicians--technician_id--attachments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-technicians--technician_id--attachments" data-method="GET"
      data-path="api/v1/technicians/{technician_id}/attachments"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-technicians--technician_id--attachments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-technicians--technician_id--attachments"
                    onclick="tryItOut('GETapi-v1-technicians--technician_id--attachments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-technicians--technician_id--attachments"
                    onclick="cancelTryOut('GETapi-v1-technicians--technician_id--attachments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-technicians--technician_id--attachments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/technicians/{technician_id}/attachments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-technicians--technician_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-technicians--technician_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_id"                data-endpoint="GETapi-v1-technicians--technician_id--attachments"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-POSTapi-v1-technicians--technician_id--attachments">POST api/v1/technicians/{technician_id}/attachments</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-technicians--technician_id--attachments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/technicians/17/attachments" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "document_type=technician_identity"\
    --form "description=Dolores dolorum amet iste laborum eius est dolor."\
    --form "file=@C:\Users\Aya\AppData\Local\Temp\php6C60.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technicians/17/attachments"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('document_type', 'technician_identity');
body.append('description', 'Dolores dolorum amet iste laborum eius est dolor.');
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-technicians--technician_id--attachments">
</span>
<span id="execution-results-POSTapi-v1-technicians--technician_id--attachments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-technicians--technician_id--attachments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-technicians--technician_id--attachments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-technicians--technician_id--attachments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-technicians--technician_id--attachments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-technicians--technician_id--attachments" data-method="POST"
      data-path="api/v1/technicians/{technician_id}/attachments"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-technicians--technician_id--attachments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-technicians--technician_id--attachments"
                    onclick="tryItOut('POSTapi-v1-technicians--technician_id--attachments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-technicians--technician_id--attachments"
                    onclick="cancelTryOut('POSTapi-v1-technicians--technician_id--attachments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-technicians--technician_id--attachments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/technicians/{technician_id}/attachments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-technicians--technician_id--attachments"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-technicians--technician_id--attachments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_id"                data-endpoint="POSTapi-v1-technicians--technician_id--attachments"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>document_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="document_type"                data-endpoint="POSTapi-v1-technicians--technician_id--attachments"
               value="technician_identity"
               data-component="body">
    <br>
<p>Example: <code>technician_identity</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>technician_certificate</code></li> <li><code>technician_identity</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-technicians--technician_id--attachments"
               value=""
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 5120 kilobytes. Example: <code>C:\Users\Aya\AppData\Local\Temp\php6C60.tmp</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-technicians--technician_id--attachments"
               value="Dolores dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>Dolores dolorum amet iste laborum eius est dolor.</code></p>
        </div>
        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-GETapi-v1-generators--generator_id--available-technicians">GET api/v1/generators/{generator_id}/available-technicians</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--available-technicians">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/available-technicians" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/available-technicians"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--available-technicians">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--available-technicians" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--available-technicians"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--available-technicians"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--available-technicians" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--available-technicians">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--available-technicians" data-method="GET"
      data-path="api/v1/generators/{generator_id}/available-technicians"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--available-technicians', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--available-technicians"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--available-technicians');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--available-technicians"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--available-technicians');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--available-technicians"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/available-technicians</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--available-technicians"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--available-technicians"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--available-technicians"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-GETapi-v1-technician-tasks">GET api/v1/technician-tasks</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-technician-tasks">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/technician-tasks" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-technician-tasks">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-technician-tasks" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-technician-tasks"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-technician-tasks"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-technician-tasks" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-technician-tasks">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-technician-tasks" data-method="GET"
      data-path="api/v1/technician-tasks"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-technician-tasks', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-technician-tasks"
                    onclick="tryItOut('GETapi-v1-technician-tasks');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-technician-tasks"
                    onclick="cancelTryOut('GETapi-v1-technician-tasks');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-technician-tasks"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/technician-tasks</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-technician-tasks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-technician-tasks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-GETapi-v1-technician-tasks--technician_task_id-">GET api/v1/technician-tasks/{technician_task_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-technician-tasks--technician_task_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/technician-tasks/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-technician-tasks--technician_task_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-technician-tasks--technician_task_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-technician-tasks--technician_task_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-technician-tasks--technician_task_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-technician-tasks--technician_task_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-technician-tasks--technician_task_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-technician-tasks--technician_task_id-" data-method="GET"
      data-path="api/v1/technician-tasks/{technician_task_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-technician-tasks--technician_task_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-technician-tasks--technician_task_id-"
                    onclick="tryItOut('GETapi-v1-technician-tasks--technician_task_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-technician-tasks--technician_task_id-"
                    onclick="cancelTryOut('GETapi-v1-technician-tasks--technician_task_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-technician-tasks--technician_task_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/technician-tasks/{technician_task_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-technician-tasks--technician_task_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-technician-tasks--technician_task_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_task_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_task_id"                data-endpoint="GETapi-v1-technician-tasks--technician_task_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician task. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-POSTapi-v1-technician-tasks">POST api/v1/technician-tasks</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-technician-tasks">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/technician-tasks" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"generator_id\": 17,
    \"type\": \"general_maintenance\",
    \"instructions\": \"mqeopfuudtdsufvyvddqa\",
    \"technician_id\": 17,
    \"taskable_type\": \"subscription\",
    \"taskable_id\": 17
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "generator_id": 17,
    "type": "general_maintenance",
    "instructions": "mqeopfuudtdsufvyvddqa",
    "technician_id": 17,
    "taskable_type": "subscription",
    "taskable_id": 17
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-technician-tasks">
</span>
<span id="execution-results-POSTapi-v1-technician-tasks" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-technician-tasks"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-technician-tasks"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-technician-tasks" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-technician-tasks">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-technician-tasks" data-method="POST"
      data-path="api/v1/technician-tasks"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-technician-tasks', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-technician-tasks"
                    onclick="tryItOut('POSTapi-v1-technician-tasks');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-technician-tasks"
                    onclick="cancelTryOut('POSTapi-v1-technician-tasks');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-technician-tasks"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/technician-tasks</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-technician-tasks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-technician-tasks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-technician-tasks"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-v1-technician-tasks"
               value="general_maintenance"
               data-component="body">
    <br>
<p>Example: <code>general_maintenance</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>new_subscription_installation</code></li> <li><code>meter_reading</code></li> <li><code>wiring_maintenance</code></li> <li><code>fault_repair</code></li> <li><code>general_maintenance</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>instructions</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="instructions"                data-endpoint="POSTapi-v1-technician-tasks"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>technician_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_id"                data-endpoint="POSTapi-v1-technician-tasks"
               value="17"
               data-component="body">
    <br>
<p>Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>taskable_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="taskable_type"                data-endpoint="POSTapi-v1-technician-tasks"
               value="subscription"
               data-component="body">
    <br>
<p>Example: <code>subscription</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>subscription</code></li> <li><code>fault</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>taskable_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="taskable_id"                data-endpoint="POSTapi-v1-technician-tasks"
               value="17"
               data-component="body">
    <br>
<p>This field is required when <code>taskable_type</code> is present. Example: <code>17</code></p>
        </div>
        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--assign">PATCH api/v1/technician-tasks/{technician_task_id}/assign</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-technician-tasks--technician_task_id--assign">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/technician-tasks/17/assign" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"technician_id\": 17
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks/17/assign"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "technician_id": 17
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-technician-tasks--technician_task_id--assign">
</span>
<span id="execution-results-PATCHapi-v1-technician-tasks--technician_task_id--assign" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-technician-tasks--technician_task_id--assign"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-technician-tasks--technician_task_id--assign"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-technician-tasks--technician_task_id--assign" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-technician-tasks--technician_task_id--assign">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-technician-tasks--technician_task_id--assign" data-method="PATCH"
      data-path="api/v1/technician-tasks/{technician_task_id}/assign"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-technician-tasks--technician_task_id--assign', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-technician-tasks--technician_task_id--assign"
                    onclick="tryItOut('PATCHapi-v1-technician-tasks--technician_task_id--assign');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-technician-tasks--technician_task_id--assign"
                    onclick="cancelTryOut('PATCHapi-v1-technician-tasks--technician_task_id--assign');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-technician-tasks--technician_task_id--assign"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/technician-tasks/{technician_task_id}/assign</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--assign"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--assign"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_task_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_task_id"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--assign"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician task. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>technician_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_id"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--assign"
               value="17"
               data-component="body">
    <br>
<p>@var TechnicianTask $task Must match an existing stored value. Example: <code>17</code></p>
        </div>
        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way">PATCH api/v1/technician-tasks/{technician_task_id}/on-the-way</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/technician-tasks/17/on-the-way" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks/17/on-the-way"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way">
</span>
<span id="execution-results-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way" data-method="PATCH"
      data-path="api/v1/technician-tasks/{technician_task_id}/on-the-way"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-technician-tasks--technician_task_id--on-the-way', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way"
                    onclick="tryItOut('PATCHapi-v1-technician-tasks--technician_task_id--on-the-way');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way"
                    onclick="cancelTryOut('PATCHapi-v1-technician-tasks--technician_task_id--on-the-way');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-technician-tasks--technician_task_id--on-the-way"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/technician-tasks/{technician_task_id}/on-the-way</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--on-the-way"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--on-the-way"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_task_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_task_id"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--on-the-way"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician task. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--start">PATCH api/v1/technician-tasks/{technician_task_id}/start</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-technician-tasks--technician_task_id--start">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/technician-tasks/17/start" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks/17/start"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-technician-tasks--technician_task_id--start">
</span>
<span id="execution-results-PATCHapi-v1-technician-tasks--technician_task_id--start" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-technician-tasks--technician_task_id--start"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-technician-tasks--technician_task_id--start"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-technician-tasks--technician_task_id--start" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-technician-tasks--technician_task_id--start">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-technician-tasks--technician_task_id--start" data-method="PATCH"
      data-path="api/v1/technician-tasks/{technician_task_id}/start"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-technician-tasks--technician_task_id--start', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-technician-tasks--technician_task_id--start"
                    onclick="tryItOut('PATCHapi-v1-technician-tasks--technician_task_id--start');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-technician-tasks--technician_task_id--start"
                    onclick="cancelTryOut('PATCHapi-v1-technician-tasks--technician_task_id--start');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-technician-tasks--technician_task_id--start"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/technician-tasks/{technician_task_id}/start</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--start"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--start"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_task_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_task_id"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--start"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician task. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts">PATCH api/v1/technician-tasks/{technician_task_id}/waiting-parts</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/technician-tasks/17/waiting-parts" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks/17/waiting-parts"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts">
</span>
<span id="execution-results-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts" data-method="PATCH"
      data-path="api/v1/technician-tasks/{technician_task_id}/waiting-parts"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts"
                    onclick="tryItOut('PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts"
                    onclick="cancelTryOut('PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/technician-tasks/{technician_task_id}/waiting-parts</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_task_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_task_id"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--waiting-parts"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician task. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--submit">PATCH api/v1/technician-tasks/{technician_task_id}/submit</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-technician-tasks--technician_task_id--submit">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/technician-tasks/17/submit" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"completion_notes\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks/17/submit"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "completion_notes": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-technician-tasks--technician_task_id--submit">
</span>
<span id="execution-results-PATCHapi-v1-technician-tasks--technician_task_id--submit" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-technician-tasks--technician_task_id--submit"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-technician-tasks--technician_task_id--submit"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-technician-tasks--technician_task_id--submit" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-technician-tasks--technician_task_id--submit">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-technician-tasks--technician_task_id--submit" data-method="PATCH"
      data-path="api/v1/technician-tasks/{technician_task_id}/submit"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-technician-tasks--technician_task_id--submit', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-technician-tasks--technician_task_id--submit"
                    onclick="tryItOut('PATCHapi-v1-technician-tasks--technician_task_id--submit');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-technician-tasks--technician_task_id--submit"
                    onclick="cancelTryOut('PATCHapi-v1-technician-tasks--technician_task_id--submit');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-technician-tasks--technician_task_id--submit"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/technician-tasks/{technician_task_id}/submit</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--submit"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--submit"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_task_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_task_id"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--submit"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician task. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>completion_notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="completion_notes"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--submit"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--review">PATCH api/v1/technician-tasks/{technician_task_id}/review</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-technician-tasks--technician_task_id--review">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/technician-tasks/17/review" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"decision\": \"rejected\",
    \"rejection_reason\": \"vmqeopfuudtdsufvyvddq\",
    \"admin_override_reason\": \"amniihfqcoynlazghdtqt\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks/17/review"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "decision": "rejected",
    "rejection_reason": "vmqeopfuudtdsufvyvddq",
    "admin_override_reason": "amniihfqcoynlazghdtqt"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-technician-tasks--technician_task_id--review">
</span>
<span id="execution-results-PATCHapi-v1-technician-tasks--technician_task_id--review" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-technician-tasks--technician_task_id--review"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-technician-tasks--technician_task_id--review"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-technician-tasks--technician_task_id--review" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-technician-tasks--technician_task_id--review">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-technician-tasks--technician_task_id--review" data-method="PATCH"
      data-path="api/v1/technician-tasks/{technician_task_id}/review"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-technician-tasks--technician_task_id--review', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-technician-tasks--technician_task_id--review"
                    onclick="tryItOut('PATCHapi-v1-technician-tasks--technician_task_id--review');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-technician-tasks--technician_task_id--review"
                    onclick="cancelTryOut('PATCHapi-v1-technician-tasks--technician_task_id--review');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-technician-tasks--technician_task_id--review"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/technician-tasks/{technician_task_id}/review</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--review"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--review"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_task_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_task_id"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--review"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician task. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>decision</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="decision"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--review"
               value="rejected"
               data-component="body">
    <br>
<p>Example: <code>rejected</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>approved</code></li> <li><code>rejected</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>rejection_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="rejection_reason"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--review"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>This field is required when <code>decision</code> is <code>rejected</code>. Must not be greater than 1000 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>admin_override_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="admin_override_reason"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--review"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
        </form>

                    <h2 id="alfnyyn-oaoamr-alshghl-PATCHapi-v1-technician-tasks--technician_task_id--cancel">PATCH api/v1/technician-tasks/{technician_task_id}/cancel</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-technician-tasks--technician_task_id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/technician-tasks/17/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/technician-tasks/17/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-technician-tasks--technician_task_id--cancel">
</span>
<span id="execution-results-PATCHapi-v1-technician-tasks--technician_task_id--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-technician-tasks--technician_task_id--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-technician-tasks--technician_task_id--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-technician-tasks--technician_task_id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-technician-tasks--technician_task_id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-technician-tasks--technician_task_id--cancel" data-method="PATCH"
      data-path="api/v1/technician-tasks/{technician_task_id}/cancel"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-technician-tasks--technician_task_id--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-technician-tasks--technician_task_id--cancel"
                    onclick="tryItOut('PATCHapi-v1-technician-tasks--technician_task_id--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-technician-tasks--technician_task_id--cancel"
                    onclick="cancelTryOut('PATCHapi-v1-technician-tasks--technician_task_id--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-technician-tasks--technician_task_id--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/technician-tasks/{technician_task_id}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>technician_task_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="technician_task_id"                data-endpoint="PATCHapi-v1-technician-tasks--technician_task_id--cancel"
               value="17"
               data-component="url">
    <br>
<p>The ID of the technician task. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="alkraaaat-oalfoatyr">القراءات والفواتير</h1>

    

                                <h2 id="alkraaaat-oalfoatyr-GETapi-v1-meter-readings">GET api/v1/meter-readings</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-meter-readings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/meter-readings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/meter-readings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-meter-readings">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-meter-readings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-meter-readings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-meter-readings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-meter-readings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-meter-readings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-meter-readings" data-method="GET"
      data-path="api/v1/meter-readings"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-meter-readings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-meter-readings"
                    onclick="tryItOut('GETapi-v1-meter-readings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-meter-readings"
                    onclick="cancelTryOut('GETapi-v1-meter-readings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-meter-readings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/meter-readings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-meter-readings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-meter-readings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alkraaaat-oalfoatyr-GETapi-v1-meter-readings--meter_reading_id-">GET api/v1/meter-readings/{meter_reading_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-meter-readings--meter_reading_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/meter-readings/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/meter-readings/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-meter-readings--meter_reading_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-meter-readings--meter_reading_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-meter-readings--meter_reading_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-meter-readings--meter_reading_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-meter-readings--meter_reading_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-meter-readings--meter_reading_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-meter-readings--meter_reading_id-" data-method="GET"
      data-path="api/v1/meter-readings/{meter_reading_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-meter-readings--meter_reading_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-meter-readings--meter_reading_id-"
                    onclick="tryItOut('GETapi-v1-meter-readings--meter_reading_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-meter-readings--meter_reading_id-"
                    onclick="cancelTryOut('GETapi-v1-meter-readings--meter_reading_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-meter-readings--meter_reading_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/meter-readings/{meter_reading_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-meter-readings--meter_reading_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-meter-readings--meter_reading_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>meter_reading_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="meter_reading_id"                data-endpoint="GETapi-v1-meter-readings--meter_reading_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the meter reading. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alkraaaat-oalfoatyr-POSTapi-v1-meter-readings">POST api/v1/meter-readings</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-meter-readings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/meter-readings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"subscription_id\": 17,
    \"reading_date\": \"2020-11-16\",
    \"current_reading\": 45
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/meter-readings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "subscription_id": 17,
    "reading_date": "2020-11-16",
    "current_reading": 45
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-meter-readings">
</span>
<span id="execution-results-POSTapi-v1-meter-readings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-meter-readings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-meter-readings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-meter-readings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-meter-readings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-meter-readings" data-method="POST"
      data-path="api/v1/meter-readings"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-meter-readings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-meter-readings"
                    onclick="tryItOut('POSTapi-v1-meter-readings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-meter-readings"
                    onclick="cancelTryOut('POSTapi-v1-meter-readings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-meter-readings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/meter-readings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-meter-readings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-meter-readings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>subscription_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscription_id"                data-endpoint="POSTapi-v1-meter-readings"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reading_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reading_date"                data-endpoint="POSTapi-v1-meter-readings"
               value="2020-11-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date before or equal to <code>today</code>. Example: <code>2020-11-16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>current_reading</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="current_reading"                data-endpoint="POSTapi-v1-meter-readings"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>45</code></p>
        </div>
        </form>

                    <h2 id="alkraaaat-oalfoatyr-GETapi-v1-invoices">GET api/v1/invoices</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-invoices">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/invoices" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/invoices"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-invoices">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-invoices" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-invoices"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-invoices"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-invoices" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-invoices">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-invoices" data-method="GET"
      data-path="api/v1/invoices"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-invoices', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-invoices"
                    onclick="tryItOut('GETapi-v1-invoices');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-invoices"
                    onclick="cancelTryOut('GETapi-v1-invoices');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-invoices"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/invoices</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-invoices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-invoices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alkraaaat-oalfoatyr-GETapi-v1-invoices-export">GET api/v1/invoices/export</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-invoices-export">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/invoices/export" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/invoices/export"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-invoices-export">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-invoices-export" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-invoices-export"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-invoices-export"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-invoices-export" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-invoices-export">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-invoices-export" data-method="GET"
      data-path="api/v1/invoices/export"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-invoices-export', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-invoices-export"
                    onclick="tryItOut('GETapi-v1-invoices-export');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-invoices-export"
                    onclick="cancelTryOut('GETapi-v1-invoices-export');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-invoices-export"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/invoices/export</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-invoices-export"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-invoices-export"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--payment-methods">GET api/v1/invoices/{invoice_id}/payment-methods</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-invoices--invoice_id--payment-methods">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/invoices/17/payment-methods" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/invoices/17/payment-methods"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-invoices--invoice_id--payment-methods">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-invoices--invoice_id--payment-methods" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-invoices--invoice_id--payment-methods"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-invoices--invoice_id--payment-methods"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-invoices--invoice_id--payment-methods" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-invoices--invoice_id--payment-methods">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-invoices--invoice_id--payment-methods" data-method="GET"
      data-path="api/v1/invoices/{invoice_id}/payment-methods"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-invoices--invoice_id--payment-methods', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-invoices--invoice_id--payment-methods"
                    onclick="tryItOut('GETapi-v1-invoices--invoice_id--payment-methods');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-invoices--invoice_id--payment-methods"
                    onclick="cancelTryOut('GETapi-v1-invoices--invoice_id--payment-methods');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-invoices--invoice_id--payment-methods"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/invoices/{invoice_id}/payment-methods</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-invoices--invoice_id--payment-methods"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-invoices--invoice_id--payment-methods"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>invoice_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="invoice_id"                data-endpoint="GETapi-v1-invoices--invoice_id--payment-methods"
               value="17"
               data-component="url">
    <br>
<p>The ID of the invoice. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alkraaaat-oalfoatyr-PATCHapi-v1-invoices--invoice_id--cancel">PATCH api/v1/invoices/{invoice_id}/cancel</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-invoices--invoice_id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/invoices/17/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/invoices/17/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-invoices--invoice_id--cancel">
</span>
<span id="execution-results-PATCHapi-v1-invoices--invoice_id--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-invoices--invoice_id--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-invoices--invoice_id--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-invoices--invoice_id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-invoices--invoice_id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-invoices--invoice_id--cancel" data-method="PATCH"
      data-path="api/v1/invoices/{invoice_id}/cancel"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-invoices--invoice_id--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-invoices--invoice_id--cancel"
                    onclick="tryItOut('PATCHapi-v1-invoices--invoice_id--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-invoices--invoice_id--cancel"
                    onclick="cancelTryOut('PATCHapi-v1-invoices--invoice_id--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-invoices--invoice_id--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/invoices/{invoice_id}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-invoices--invoice_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-invoices--invoice_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>invoice_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="invoice_id"                data-endpoint="PATCHapi-v1-invoices--invoice_id--cancel"
               value="17"
               data-component="url">
    <br>
<p>The ID of the invoice. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--pdf">GET api/v1/invoices/{invoice_id}/pdf</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-invoices--invoice_id--pdf">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/invoices/17/pdf" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/invoices/17/pdf"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-invoices--invoice_id--pdf">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-invoices--invoice_id--pdf" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-invoices--invoice_id--pdf"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-invoices--invoice_id--pdf"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-invoices--invoice_id--pdf" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-invoices--invoice_id--pdf">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-invoices--invoice_id--pdf" data-method="GET"
      data-path="api/v1/invoices/{invoice_id}/pdf"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-invoices--invoice_id--pdf', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-invoices--invoice_id--pdf"
                    onclick="tryItOut('GETapi-v1-invoices--invoice_id--pdf');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-invoices--invoice_id--pdf"
                    onclick="cancelTryOut('GETapi-v1-invoices--invoice_id--pdf');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-invoices--invoice_id--pdf"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/invoices/{invoice_id}/pdf</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-invoices--invoice_id--pdf"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-invoices--invoice_id--pdf"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>invoice_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="invoice_id"                data-endpoint="GETapi-v1-invoices--invoice_id--pdf"
               value="17"
               data-component="url">
    <br>
<p>The ID of the invoice. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id--qr">GET api/v1/invoices/{invoice_id}/qr</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-invoices--invoice_id--qr">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/invoices/17/qr" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/invoices/17/qr"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-invoices--invoice_id--qr">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-invoices--invoice_id--qr" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-invoices--invoice_id--qr"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-invoices--invoice_id--qr"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-invoices--invoice_id--qr" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-invoices--invoice_id--qr">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-invoices--invoice_id--qr" data-method="GET"
      data-path="api/v1/invoices/{invoice_id}/qr"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-invoices--invoice_id--qr', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-invoices--invoice_id--qr"
                    onclick="tryItOut('GETapi-v1-invoices--invoice_id--qr');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-invoices--invoice_id--qr"
                    onclick="cancelTryOut('GETapi-v1-invoices--invoice_id--qr');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-invoices--invoice_id--qr"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/invoices/{invoice_id}/qr</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-invoices--invoice_id--qr"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-invoices--invoice_id--qr"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>invoice_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="invoice_id"                data-endpoint="GETapi-v1-invoices--invoice_id--qr"
               value="17"
               data-component="url">
    <br>
<p>The ID of the invoice. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alkraaaat-oalfoatyr-GETapi-v1-invoices--invoice_id-">GET api/v1/invoices/{invoice_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-invoices--invoice_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/invoices/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/invoices/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-invoices--invoice_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-invoices--invoice_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-invoices--invoice_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-invoices--invoice_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-invoices--invoice_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-invoices--invoice_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-invoices--invoice_id-" data-method="GET"
      data-path="api/v1/invoices/{invoice_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-invoices--invoice_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-invoices--invoice_id-"
                    onclick="tryItOut('GETapi-v1-invoices--invoice_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-invoices--invoice_id-"
                    onclick="cancelTryOut('GETapi-v1-invoices--invoice_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-invoices--invoice_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/invoices/{invoice_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-invoices--invoice_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-invoices--invoice_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>invoice_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="invoice_id"                data-endpoint="GETapi-v1-invoices--invoice_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the invoice. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="almhadthat">المحادثات</h1>

    

                                <h2 id="almhadthat-GETapi-v1-conversations">GET api/v1/conversations</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-conversations">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/conversations" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/conversations"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-conversations">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-conversations" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-conversations"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-conversations"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-conversations" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-conversations">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-conversations" data-method="GET"
      data-path="api/v1/conversations"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-conversations', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-conversations"
                    onclick="tryItOut('GETapi-v1-conversations');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-conversations"
                    onclick="cancelTryOut('GETapi-v1-conversations');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-conversations"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/conversations</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-conversations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-conversations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="almhadthat-POSTapi-v1-conversations">POST api/v1/conversations</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-conversations">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/conversations" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"user_id\": 17
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/conversations"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "user_id": 17
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-conversations">
</span>
<span id="execution-results-POSTapi-v1-conversations" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-conversations"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-conversations"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-conversations" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-conversations">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-conversations" data-method="POST"
      data-path="api/v1/conversations"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-conversations', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-conversations"
                    onclick="tryItOut('POSTapi-v1-conversations');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-conversations"
                    onclick="cancelTryOut('POSTapi-v1-conversations');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-conversations"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/conversations</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-conversations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-conversations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="POSTapi-v1-conversations"
               value="17"
               data-component="body">
    <br>
<p>The value and <code></code> must be different. Must match an existing stored value. Example: <code>17</code></p>
        </div>
        </form>

                    <h2 id="almhadthat-GETapi-v1-conversations--id-">GET api/v1/conversations/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-conversations--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/conversations/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/conversations/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-conversations--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-conversations--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-conversations--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-conversations--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-conversations--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-conversations--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-conversations--id-" data-method="GET"
      data-path="api/v1/conversations/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-conversations--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-conversations--id-"
                    onclick="tryItOut('GETapi-v1-conversations--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-conversations--id-"
                    onclick="cancelTryOut('GETapi-v1-conversations--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-conversations--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/conversations/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-conversations--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-conversations--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-conversations--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the conversation. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="almhadthat-DELETEapi-v1-conversations--id-">DELETE api/v1/conversations/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-conversations--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/conversations/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/conversations/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-conversations--id-">
</span>
<span id="execution-results-DELETEapi-v1-conversations--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-conversations--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-conversations--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-conversations--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-conversations--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-conversations--id-" data-method="DELETE"
      data-path="api/v1/conversations/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-conversations--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-conversations--id-"
                    onclick="tryItOut('DELETEapi-v1-conversations--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-conversations--id-"
                    onclick="cancelTryOut('DELETEapi-v1-conversations--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-conversations--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/conversations/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-conversations--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-conversations--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-conversations--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the conversation. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="almhadthat-GETapi-v1-conversations--conversation_id--messages">GET api/v1/conversations/{conversation_id}/messages</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-conversations--conversation_id--messages">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/conversations/17/messages" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/conversations/17/messages"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-conversations--conversation_id--messages">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-conversations--conversation_id--messages" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-conversations--conversation_id--messages"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-conversations--conversation_id--messages"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-conversations--conversation_id--messages" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-conversations--conversation_id--messages">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-conversations--conversation_id--messages" data-method="GET"
      data-path="api/v1/conversations/{conversation_id}/messages"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-conversations--conversation_id--messages', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-conversations--conversation_id--messages"
                    onclick="tryItOut('GETapi-v1-conversations--conversation_id--messages');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-conversations--conversation_id--messages"
                    onclick="cancelTryOut('GETapi-v1-conversations--conversation_id--messages');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-conversations--conversation_id--messages"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/conversations/{conversation_id}/messages</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-conversations--conversation_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-conversations--conversation_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>conversation_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="conversation_id"                data-endpoint="GETapi-v1-conversations--conversation_id--messages"
               value="17"
               data-component="url">
    <br>
<p>The ID of the conversation. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="almhadthat-POSTapi-v1-conversations--conversation_id--messages">POST api/v1/conversations/{conversation_id}/messages</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-conversations--conversation_id--messages">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/conversations/17/messages" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"message_text\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/conversations/17/messages"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "message_text": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-conversations--conversation_id--messages">
</span>
<span id="execution-results-POSTapi-v1-conversations--conversation_id--messages" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-conversations--conversation_id--messages"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-conversations--conversation_id--messages"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-conversations--conversation_id--messages" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-conversations--conversation_id--messages">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-conversations--conversation_id--messages" data-method="POST"
      data-path="api/v1/conversations/{conversation_id}/messages"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-conversations--conversation_id--messages', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-conversations--conversation_id--messages"
                    onclick="tryItOut('POSTapi-v1-conversations--conversation_id--messages');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-conversations--conversation_id--messages"
                    onclick="cancelTryOut('POSTapi-v1-conversations--conversation_id--messages');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-conversations--conversation_id--messages"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/conversations/{conversation_id}/messages</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-conversations--conversation_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-conversations--conversation_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>conversation_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="conversation_id"                data-endpoint="POSTapi-v1-conversations--conversation_id--messages"
               value="17"
               data-component="url">
    <br>
<p>The ID of the conversation. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>message_text</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="message_text"                data-endpoint="POSTapi-v1-conversations--conversation_id--messages"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                <h1 id="almrfkat">المرفقات</h1>

    

                                <h2 id="almrfkat-GETapi-v1-attachments--id-">GET api/v1/attachments/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-attachments--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/attachments/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/attachments/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-attachments--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-attachments--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-attachments--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-attachments--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-attachments--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-attachments--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-attachments--id-" data-method="GET"
      data-path="api/v1/attachments/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-attachments--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-attachments--id-"
                    onclick="tryItOut('GETapi-v1-attachments--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-attachments--id-"
                    onclick="cancelTryOut('GETapi-v1-attachments--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-attachments--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/attachments/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-attachments--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-attachments--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-attachments--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the attachment. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="almrfkat-GETapi-v1-attachments--attachment_id--download">GET api/v1/attachments/{attachment_id}/download</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-attachments--attachment_id--download">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/attachments/17/download" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/attachments/17/download"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-attachments--attachment_id--download">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-attachments--attachment_id--download" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-attachments--attachment_id--download"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-attachments--attachment_id--download"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-attachments--attachment_id--download" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-attachments--attachment_id--download">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-attachments--attachment_id--download" data-method="GET"
      data-path="api/v1/attachments/{attachment_id}/download"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-attachments--attachment_id--download', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-attachments--attachment_id--download"
                    onclick="tryItOut('GETapi-v1-attachments--attachment_id--download');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-attachments--attachment_id--download"
                    onclick="cancelTryOut('GETapi-v1-attachments--attachment_id--download');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-attachments--attachment_id--download"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/attachments/{attachment_id}/download</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-attachments--attachment_id--download"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-attachments--attachment_id--download"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>attachment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="attachment_id"                data-endpoint="GETapi-v1-attachments--attachment_id--download"
               value="17"
               data-component="url">
    <br>
<p>The ID of the attachment. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="almrfkat-DELETEapi-v1-attachments--id-">DELETE api/v1/attachments/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-attachments--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/attachments/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/attachments/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-attachments--id-">
</span>
<span id="execution-results-DELETEapi-v1-attachments--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-attachments--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-attachments--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-attachments--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-attachments--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-attachments--id-" data-method="DELETE"
      data-path="api/v1/attachments/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-attachments--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-attachments--id-"
                    onclick="tryItOut('DELETEapi-v1-attachments--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-attachments--id-"
                    onclick="cancelTryOut('DELETEapi-v1-attachments--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-attachments--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/attachments/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-attachments--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-attachments--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-attachments--id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the attachment. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="almsaaad-althky-oaltshkhys">المساعد الذكي والتشخيص</h1>

    

                                <h2 id="almsaaad-althky-oaltshkhys-GETapi-v1-generators--generator_id--diagnostics">GET api/v1/generators/{generator_id}/diagnostics</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--diagnostics">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/diagnostics" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/diagnostics"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--diagnostics">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--diagnostics" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--diagnostics"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--diagnostics"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--diagnostics" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--diagnostics">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--diagnostics" data-method="GET"
      data-path="api/v1/generators/{generator_id}/diagnostics"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--diagnostics', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--diagnostics"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--diagnostics');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--diagnostics"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--diagnostics');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--diagnostics"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/diagnostics</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--diagnostics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--diagnostics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--diagnostics"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="almsaaad-althky-oaltshkhys-POSTapi-v1-generators--generator_id--diagnostics">POST api/v1/generators/{generator_id}/diagnostics</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-generators--generator_id--diagnostics">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/generators/17/diagnostics" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"operating_hours\": 73,
    \"temperature_celsius\": 13,
    \"oil_level_percent\": 16,
    \"load_percent\": 5,
    \"voltage\": 50,
    \"frequency_hz\": 55,
    \"smoke_level\": \"heavy\",
    \"vibration_level\": \"abnormal\",
    \"notes\": \"fuudtdsufvyvddqamniih\",
    \"reading_date\": \"2020-11-16\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/diagnostics"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "operating_hours": 73,
    "temperature_celsius": 13,
    "oil_level_percent": 16,
    "load_percent": 5,
    "voltage": 50,
    "frequency_hz": 55,
    "smoke_level": "heavy",
    "vibration_level": "abnormal",
    "notes": "fuudtdsufvyvddqamniih",
    "reading_date": "2020-11-16"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-generators--generator_id--diagnostics">
</span>
<span id="execution-results-POSTapi-v1-generators--generator_id--diagnostics" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-generators--generator_id--diagnostics"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-generators--generator_id--diagnostics"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-generators--generator_id--diagnostics" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-generators--generator_id--diagnostics">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-generators--generator_id--diagnostics" data-method="POST"
      data-path="api/v1/generators/{generator_id}/diagnostics"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-generators--generator_id--diagnostics', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-generators--generator_id--diagnostics"
                    onclick="tryItOut('POSTapi-v1-generators--generator_id--diagnostics');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-generators--generator_id--diagnostics"
                    onclick="cancelTryOut('POSTapi-v1-generators--generator_id--diagnostics');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-generators--generator_id--diagnostics"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/generators/{generator_id}/diagnostics</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>operating_hours</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="operating_hours"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="73"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>73</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>temperature_celsius</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="temperature_celsius"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="13"
               data-component="body">
    <br>
<p>Must be at least -40. Must not be greater than 200. Example: <code>13</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>oil_level_percent</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="oil_level_percent"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="16"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 100. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>load_percent</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="load_percent"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="5"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 100. Example: <code>5</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voltage</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voltage"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="50"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>50</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>frequency_hz</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="frequency_hz"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="55"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>55</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>smoke_level</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="smoke_level"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="heavy"
               data-component="body">
    <br>
<p>Example: <code>heavy</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>none</code></li> <li><code>light</code></li> <li><code>heavy</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>vibration_level</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="vibration_level"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="abnormal"
               data-component="body">
    <br>
<p>Example: <code>abnormal</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>normal</code></li> <li><code>abnormal</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="fuudtdsufvyvddqamniih"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>fuudtdsufvyvddqamniih</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reading_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reading_date"                data-endpoint="POSTapi-v1-generators--generator_id--diagnostics"
               value="2020-11-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date before or equal to <code>today</code>. Example: <code>2020-11-16</code></p>
        </div>
        </form>

                    <h2 id="almsaaad-althky-oaltshkhys-POSTapi-v1-generator-diagnostics--reading_id--analyze">POST api/v1/generator-diagnostics/{reading_id}/analyze</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-generator-diagnostics--reading_id--analyze">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/generator-diagnostics/17/analyze" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generator-diagnostics/17/analyze"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-generator-diagnostics--reading_id--analyze">
</span>
<span id="execution-results-POSTapi-v1-generator-diagnostics--reading_id--analyze" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-generator-diagnostics--reading_id--analyze"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-generator-diagnostics--reading_id--analyze"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-generator-diagnostics--reading_id--analyze" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-generator-diagnostics--reading_id--analyze">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-generator-diagnostics--reading_id--analyze" data-method="POST"
      data-path="api/v1/generator-diagnostics/{reading_id}/analyze"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-generator-diagnostics--reading_id--analyze', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-generator-diagnostics--reading_id--analyze"
                    onclick="tryItOut('POSTapi-v1-generator-diagnostics--reading_id--analyze');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-generator-diagnostics--reading_id--analyze"
                    onclick="cancelTryOut('POSTapi-v1-generator-diagnostics--reading_id--analyze');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-generator-diagnostics--reading_id--analyze"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/generator-diagnostics/{reading_id}/analyze</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-generator-diagnostics--reading_id--analyze"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-generator-diagnostics--reading_id--analyze"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reading_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="reading_id"                data-endpoint="POSTapi-v1-generator-diagnostics--reading_id--analyze"
               value="17"
               data-component="url">
    <br>
<p>The ID of the reading. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-available-generators">GET api/v1/ai-chat/available-generators</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-ai-chat-available-generators">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/ai-chat/available-generators" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/ai-chat/available-generators"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-ai-chat-available-generators">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-ai-chat-available-generators" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-ai-chat-available-generators"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-ai-chat-available-generators"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-ai-chat-available-generators" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-ai-chat-available-generators">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-ai-chat-available-generators" data-method="GET"
      data-path="api/v1/ai-chat/available-generators"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-ai-chat-available-generators', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-ai-chat-available-generators"
                    onclick="tryItOut('GETapi-v1-ai-chat-available-generators');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-ai-chat-available-generators"
                    onclick="cancelTryOut('GETapi-v1-ai-chat-available-generators');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-ai-chat-available-generators"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/ai-chat/available-generators</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-ai-chat-available-generators"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-ai-chat-available-generators"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-sessions">GET api/v1/ai-chat/sessions</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-ai-chat-sessions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/ai-chat/sessions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/ai-chat/sessions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-ai-chat-sessions">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-ai-chat-sessions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-ai-chat-sessions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-ai-chat-sessions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-ai-chat-sessions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-ai-chat-sessions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-ai-chat-sessions" data-method="GET"
      data-path="api/v1/ai-chat/sessions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-ai-chat-sessions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-ai-chat-sessions"
                    onclick="tryItOut('GETapi-v1-ai-chat-sessions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-ai-chat-sessions"
                    onclick="cancelTryOut('GETapi-v1-ai-chat-sessions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-ai-chat-sessions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/ai-chat/sessions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-ai-chat-sessions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-ai-chat-sessions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="almsaaad-althky-oaltshkhys-GETapi-v1-ai-chat-sessions--aiChatSession_id-">GET api/v1/ai-chat/sessions/{aiChatSession_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-ai-chat-sessions--aiChatSession_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/ai-chat/sessions/17" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/ai-chat/sessions/17"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-ai-chat-sessions--aiChatSession_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-ai-chat-sessions--aiChatSession_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-ai-chat-sessions--aiChatSession_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-ai-chat-sessions--aiChatSession_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-ai-chat-sessions--aiChatSession_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-ai-chat-sessions--aiChatSession_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-ai-chat-sessions--aiChatSession_id-" data-method="GET"
      data-path="api/v1/ai-chat/sessions/{aiChatSession_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-ai-chat-sessions--aiChatSession_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-ai-chat-sessions--aiChatSession_id-"
                    onclick="tryItOut('GETapi-v1-ai-chat-sessions--aiChatSession_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-ai-chat-sessions--aiChatSession_id-"
                    onclick="cancelTryOut('GETapi-v1-ai-chat-sessions--aiChatSession_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-ai-chat-sessions--aiChatSession_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/ai-chat/sessions/{aiChatSession_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-ai-chat-sessions--aiChatSession_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-ai-chat-sessions--aiChatSession_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>aiChatSession_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="aiChatSession_id"                data-endpoint="GETapi-v1-ai-chat-sessions--aiChatSession_id-"
               value="17"
               data-component="url">
    <br>
<p>The ID of the aiChatSession. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions">POST api/v1/ai-chat/sessions</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-ai-chat-sessions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/ai-chat/sessions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"generator_id\": 17,
    \"message\": \"mqeopfuudtdsufvyvddqa\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/ai-chat/sessions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "generator_id": 17,
    "message": "mqeopfuudtdsufvyvddqa"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-ai-chat-sessions">
</span>
<span id="execution-results-POSTapi-v1-ai-chat-sessions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-ai-chat-sessions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-ai-chat-sessions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-ai-chat-sessions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-ai-chat-sessions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-ai-chat-sessions" data-method="POST"
      data-path="api/v1/ai-chat/sessions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-ai-chat-sessions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-ai-chat-sessions"
                    onclick="tryItOut('POSTapi-v1-ai-chat-sessions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-ai-chat-sessions"
                    onclick="cancelTryOut('POSTapi-v1-ai-chat-sessions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-ai-chat-sessions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/ai-chat/sessions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-ai-chat-sessions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-ai-chat-sessions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-ai-chat-sessions"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>message</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="message"                data-endpoint="POSTapi-v1-ai-chat-sessions"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
        </form>

                    <h2 id="almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages">POST api/v1/ai-chat/sessions/{aiChatSession_id}/messages</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/ai-chat/sessions/17/messages" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"message\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/ai-chat/sessions/17/messages"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "message": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages">
</span>
<span id="execution-results-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages" data-method="POST"
      data-path="api/v1/ai-chat/sessions/{aiChatSession_id}/messages"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"
                    onclick="tryItOut('POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"
                    onclick="cancelTryOut('POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/ai-chat/sessions/{aiChatSession_id}/messages</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>aiChatSession_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="aiChatSession_id"                data-endpoint="POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"
               value="17"
               data-component="url">
    <br>
<p>The ID of the aiChatSession. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>message</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="message"                data-endpoint="POSTapi-v1-ai-chat-sessions--aiChatSession_id--messages"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                    <h2 id="almsaaad-althky-oaltshkhys-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction">POST api/v1/ai-chat/sessions/{aiChatSession_id}/submit-as-prediction</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/ai-chat/sessions/17/submit-as-prediction" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/ai-chat/sessions/17/submit-as-prediction"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction">
</span>
<span id="execution-results-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction" data-method="POST"
      data-path="api/v1/ai-chat/sessions/{aiChatSession_id}/submit-as-prediction"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction"
                    onclick="tryItOut('POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction"
                    onclick="cancelTryOut('POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/ai-chat/sessions/{aiChatSession_id}/submit-as-prediction</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>aiChatSession_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="aiChatSession_id"                data-endpoint="POSTapi-v1-ai-chat-sessions--aiChatSession_id--submit-as-prediction"
               value="17"
               data-component="url">
    <br>
<p>The ID of the aiChatSession. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="almshtrkyn-oalaadadat">المشتركين والعدادات</h1>

    

                                <h2 id="almshtrkyn-oalaadadat-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type">PATCH api/v1/subscribers/{subscriber_id}/beneficiary-type</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/subscribers/17/beneficiary-type" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"beneficiary_type\": \"special\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/subscribers/17/beneficiary-type"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "beneficiary_type": "special"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type">
</span>
<span id="execution-results-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type" data-method="PATCH"
      data-path="api/v1/subscribers/{subscriber_id}/beneficiary-type"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-subscribers--subscriber_id--beneficiary-type', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"
                    onclick="tryItOut('PATCHapi-v1-subscribers--subscriber_id--beneficiary-type');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"
                    onclick="cancelTryOut('PATCHapi-v1-subscribers--subscriber_id--beneficiary-type');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/subscribers/{subscriber_id}/beneficiary-type</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subscriber_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="subscriber_id"                data-endpoint="PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"
               value="17"
               data-component="url">
    <br>
<p>The ID of the subscriber. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>beneficiary_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="beneficiary_type"                data-endpoint="PATCHapi-v1-subscribers--subscriber_id--beneficiary-type"
               value="special"
               data-component="body">
    <br>
<p>Example: <code>special</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>normal</code></li> <li><code>special</code></li></ul>
        </div>
        </form>

                <h1 id="almsadk-oadar-alhsab">المصادقة وإدارة الحساب</h1>

    

                                <h2 id="almsadk-oadar-alhsab-POSTapi-v1-auth-register">POST api/v1/auth/register</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-register">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/register" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"email\": \"kunde.eloisa@example.com\",
    \"phone\": \"562771717728\",
    \"neighborhood_id\": 17,
    \"address\": \"mqeopfuudtdsufvyvddqa\",
    \"password\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/register"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "email": "kunde.eloisa@example.com",
    "phone": "562771717728",
    "neighborhood_id": 17,
    "address": "mqeopfuudtdsufvyvddqa",
    "password": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-register">
</span>
<span id="execution-results-POSTapi-v1-auth-register" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-register"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-register"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-register">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-register" data-method="POST"
      data-path="api/v1/auth/register"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-register', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-register"
                    onclick="tryItOut('POSTapi-v1-auth-register');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-register"
                    onclick="cancelTryOut('POSTapi-v1-auth-register');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-register"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/register</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-auth-register"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must be at least 3 characters. Must not be greater than 100 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-register"
               value="kunde.eloisa@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>kunde.eloisa@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-auth-register"
               value="562771717728"
               data-component="body">
    <br>
<p>Must match the regex /^+?[0-9]{7,15}$/. Example: <code>562771717728</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>neighborhood_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="neighborhood_id"                data-endpoint="POSTapi-v1-auth-register"
               value="17"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>address</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address"                data-endpoint="POSTapi-v1-auth-register"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-auth-register"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="almsadk-oadar-alhsab-POSTapi-v1-auth-login">POST api/v1/auth/login</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"qkunze@example.com\",
    \"password\": \"O[2UZ5ij-e\\/dl4m{o,\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "qkunze@example.com",
    "password": "O[2UZ5ij-e\/dl4m{o,"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-login">
</span>
<span id="execution-results-POSTapi-v1-auth-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-login" data-method="POST"
      data-path="api/v1/auth/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-login"
                    onclick="tryItOut('POSTapi-v1-auth-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-login"
                    onclick="cancelTryOut('POSTapi-v1-auth-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-login"
               value="qkunze@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>qkunze@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-auth-login"
               value="O[2UZ5ij-e/dl4m{o,"
               data-component="body">
    <br>
<p>Example: <code>O[2UZ5ij-e/dl4m{o,</code></p>
        </div>
        </form>

                    <h2 id="almsadk-oadar-alhsab-POSTapi-v1-auth-forgot-password">POST api/v1/auth/forgot-password</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-forgot-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/forgot-password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"qkunze@example.com\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/forgot-password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "qkunze@example.com"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-forgot-password">
</span>
<span id="execution-results-POSTapi-v1-auth-forgot-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-forgot-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-forgot-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-forgot-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-forgot-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-forgot-password" data-method="POST"
      data-path="api/v1/auth/forgot-password"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-forgot-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-forgot-password"
                    onclick="tryItOut('POSTapi-v1-auth-forgot-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-forgot-password"
                    onclick="cancelTryOut('POSTapi-v1-auth-forgot-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-forgot-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/forgot-password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-forgot-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-forgot-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-forgot-password"
               value="qkunze@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>qkunze@example.com</code></p>
        </div>
        </form>

                    <h2 id="almsadk-oadar-alhsab-POSTapi-v1-auth-reset-password">POST api/v1/auth/reset-password</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-reset-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/reset-password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"token\": \"consequatur\",
    \"email\": \"carolyne.luettgen@example.org\",
    \"password\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/reset-password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "token": "consequatur",
    "email": "carolyne.luettgen@example.org",
    "password": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-reset-password">
</span>
<span id="execution-results-POSTapi-v1-auth-reset-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-reset-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-reset-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-reset-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-reset-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-reset-password" data-method="POST"
      data-path="api/v1/auth/reset-password"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-reset-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-reset-password"
                    onclick="tryItOut('POSTapi-v1-auth-reset-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-reset-password"
                    onclick="cancelTryOut('POSTapi-v1-auth-reset-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-reset-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/reset-password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-reset-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-reset-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="token"                data-endpoint="POSTapi-v1-auth-reset-password"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-reset-password"
               value="carolyne.luettgen@example.org"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>carolyne.luettgen@example.org</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-auth-reset-password"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="almsadk-oadar-alhsab-GETapi-v1-auth-verify-email--id---hash-">GET api/v1/auth/verify-email/{id}/{hash}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-auth-verify-email--id---hash-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/auth/verify-email/consequatur/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/verify-email/consequatur/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-auth-verify-email--id---hash-">
            <blockquote>
            <p>Example response (500):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 6
x-ratelimit-remaining: 4
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;حدث خطأ غير متوقع، يرجى المحاولة لاحقًا.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-auth-verify-email--id---hash-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-auth-verify-email--id---hash-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-auth-verify-email--id---hash-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-auth-verify-email--id---hash-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-auth-verify-email--id---hash-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-auth-verify-email--id---hash-" data-method="GET"
      data-path="api/v1/auth/verify-email/{id}/{hash}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-auth-verify-email--id---hash-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-auth-verify-email--id---hash-"
                    onclick="tryItOut('GETapi-v1-auth-verify-email--id---hash-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-auth-verify-email--id---hash-"
                    onclick="cancelTryOut('GETapi-v1-auth-verify-email--id---hash-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-auth-verify-email--id---hash-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/auth/verify-email/{id}/{hash}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-auth-verify-email--id---hash-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-auth-verify-email--id---hash-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-v1-auth-verify-email--id---hash-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the verify email. Example: <code>consequatur</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>hash</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="hash"                data-endpoint="GETapi-v1-auth-verify-email--id---hash-"
               value="consequatur"
               data-component="url">
    <br>
<p>Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="almsadk-oadar-alhsab-POSTapi-v1-auth-email-resend">POST api/v1/auth/email/resend</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-email-resend">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/email/resend" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"qkunze@example.com\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/email/resend"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "qkunze@example.com"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-email-resend">
</span>
<span id="execution-results-POSTapi-v1-auth-email-resend" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-email-resend"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-email-resend"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-email-resend" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-email-resend">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-email-resend" data-method="POST"
      data-path="api/v1/auth/email/resend"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-email-resend', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-email-resend"
                    onclick="tryItOut('POSTapi-v1-auth-email-resend');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-email-resend"
                    onclick="cancelTryOut('POSTapi-v1-auth-email-resend');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-email-resend"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/email/resend</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-email-resend"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-email-resend"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-auth-email-resend"
               value="qkunze@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>qkunze@example.com</code></p>
        </div>
        </form>

                    <h2 id="almsadk-oadar-alhsab-POSTapi-v1-auth-logout">POST api/v1/auth/logout</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/logout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-logout">
</span>
<span id="execution-results-POSTapi-v1-auth-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-logout" data-method="POST"
      data-path="api/v1/auth/logout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-logout"
                    onclick="tryItOut('POSTapi-v1-auth-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-logout"
                    onclick="cancelTryOut('POSTapi-v1-auth-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="almsadk-oadar-alhsab-GETapi-v1-auth-me">GET api/v1/auth/me</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-auth-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/auth/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/me"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-auth-me">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-auth-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-auth-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-auth-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-auth-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-auth-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-auth-me" data-method="GET"
      data-path="api/v1/auth/me"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-auth-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-auth-me"
                    onclick="tryItOut('GETapi-v1-auth-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-auth-me"
                    onclick="cancelTryOut('GETapi-v1-auth-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-auth-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/auth/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-auth-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-auth-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="almsadk-oadar-alhsab-PATCHapi-v1-auth-password">PATCH api/v1/auth/password</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-auth-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/auth/password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"current_password\": \"consequatur\",
    \"password\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "current_password": "consequatur",
    "password": "consequatur"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-auth-password">
</span>
<span id="execution-results-PATCHapi-v1-auth-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-auth-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-auth-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-auth-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-auth-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-auth-password" data-method="PATCH"
      data-path="api/v1/auth/password"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-auth-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-auth-password"
                    onclick="tryItOut('PATCHapi-v1-auth-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-auth-password"
                    onclick="cancelTryOut('PATCHapi-v1-auth-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-auth-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/auth/password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-auth-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-auth-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>current_password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="current_password"                data-endpoint="PATCHapi-v1-auth-password"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="PATCHapi-v1-auth-password"
               value="consequatur"
               data-component="body">
    <br>
<p>The value and <code>current_password</code> must be different. Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="almsadk-oadar-alhsab-POSTapi-v1-auth-logout-other-devices">POST api/v1/auth/logout-other-devices</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-logout-other-devices">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/auth/logout-other-devices" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/logout-other-devices"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-logout-other-devices">
</span>
<span id="execution-results-POSTapi-v1-auth-logout-other-devices" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-logout-other-devices"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-logout-other-devices"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-logout-other-devices" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-logout-other-devices">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-logout-other-devices" data-method="POST"
      data-path="api/v1/auth/logout-other-devices"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-logout-other-devices', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-logout-other-devices"
                    onclick="tryItOut('POSTapi-v1-auth-logout-other-devices');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-logout-other-devices"
                    onclick="cancelTryOut('POSTapi-v1-auth-logout-other-devices');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-logout-other-devices"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/logout-other-devices</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-logout-other-devices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-logout-other-devices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="alokod">الوقود</h1>

    

                                <h2 id="alokod-GETapi-v1-generators--generator_id--fuel-purchases">GET api/v1/generators/{generator_id}/fuel/purchases</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--fuel-purchases">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/fuel/purchases" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/fuel/purchases"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--fuel-purchases">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--fuel-purchases" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--fuel-purchases"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--fuel-purchases"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--fuel-purchases" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--fuel-purchases">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--fuel-purchases" data-method="GET"
      data-path="api/v1/generators/{generator_id}/fuel/purchases"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--fuel-purchases', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--fuel-purchases"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--fuel-purchases');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--fuel-purchases"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--fuel-purchases');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--fuel-purchases"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/fuel/purchases</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--fuel-purchases"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--fuel-purchases"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--fuel-purchases"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alokod-POSTapi-v1-generators--generator_id--fuel-purchases">POST api/v1/generators/{generator_id}/fuel/purchases</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-generators--generator_id--fuel-purchases">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/generators/17/fuel/purchases" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "liters=21"\
    --form "cost_amount=45"\
    --form "currency=ILS"\
    --form "purchased_at=2020-11-16"\
    --form "notes=mqeopfuudtdsufvyvddqa"\
    --form "attachments[]=@C:\Users\Aya\AppData\Local\Temp\php6D6B.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/fuel/purchases"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('liters', '21');
body.append('cost_amount', '45');
body.append('currency', 'ILS');
body.append('purchased_at', '2020-11-16');
body.append('notes', 'mqeopfuudtdsufvyvddqa');
body.append('attachments[]', document.querySelector('input[name="attachments[]"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-generators--generator_id--fuel-purchases">
</span>
<span id="execution-results-POSTapi-v1-generators--generator_id--fuel-purchases" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-generators--generator_id--fuel-purchases"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-generators--generator_id--fuel-purchases"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-generators--generator_id--fuel-purchases" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-generators--generator_id--fuel-purchases">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-generators--generator_id--fuel-purchases" data-method="POST"
      data-path="api/v1/generators/{generator_id}/fuel/purchases"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-generators--generator_id--fuel-purchases', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-generators--generator_id--fuel-purchases"
                    onclick="tryItOut('POSTapi-v1-generators--generator_id--fuel-purchases');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-generators--generator_id--fuel-purchases"
                    onclick="cancelTryOut('POSTapi-v1-generators--generator_id--fuel-purchases');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-generators--generator_id--fuel-purchases"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/generators/{generator_id}/fuel/purchases</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>liters</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="liters"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               value="21"
               data-component="body">
    <br>
<p>Must be at least 0.1. Must not be greater than 99999.99. Example: <code>21</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>cost_amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="cost_amount"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               value="ILS"
               data-component="body">
    <br>
<p>Example: <code>ILS</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ILS</code></li> <li><code>USD</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>purchased_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="purchased_at"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               value="2020-11-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date before or equal to <code>today</code>. Example: <code>2020-11-16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>attachments</code></b>&nbsp;&nbsp;
<small>file[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="attachments[0]"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               data-component="body">
        <input type="file" style="display: none"
               name="attachments[1]"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-purchases"
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 5120 kilobytes.</p>
        </div>
        </form>

                    <h2 id="alokod-GETapi-v1-generators--generator_id--fuel-readings">GET api/v1/generators/{generator_id}/fuel/readings</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--fuel-readings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/fuel/readings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/fuel/readings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--fuel-readings">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--fuel-readings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--fuel-readings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--fuel-readings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--fuel-readings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--fuel-readings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--fuel-readings" data-method="GET"
      data-path="api/v1/generators/{generator_id}/fuel/readings"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--fuel-readings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--fuel-readings"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--fuel-readings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--fuel-readings"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--fuel-readings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--fuel-readings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/fuel/readings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--fuel-readings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--fuel-readings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--fuel-readings"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                    <h2 id="alokod-POSTapi-v1-generators--generator_id--fuel-readings">POST api/v1/generators/{generator_id}/fuel/readings</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-generators--generator_id--fuel-readings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/generators/17/fuel/readings" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "tank_level_liters=21"\
    --form "meter_hours=13"\
    --form "reading_date=2020-11-16"\
    --form "notes=mqeopfuudtdsufvyvddqa"\
    --form "attachments[]=@C:\Users\Aya\AppData\Local\Temp\php6D7C.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/fuel/readings"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('tank_level_liters', '21');
body.append('meter_hours', '13');
body.append('reading_date', '2020-11-16');
body.append('notes', 'mqeopfuudtdsufvyvddqa');
body.append('attachments[]', document.querySelector('input[name="attachments[]"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-generators--generator_id--fuel-readings">
</span>
<span id="execution-results-POSTapi-v1-generators--generator_id--fuel-readings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-generators--generator_id--fuel-readings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-generators--generator_id--fuel-readings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-generators--generator_id--fuel-readings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-generators--generator_id--fuel-readings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-generators--generator_id--fuel-readings" data-method="POST"
      data-path="api/v1/generators/{generator_id}/fuel/readings"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-generators--generator_id--fuel-readings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-generators--generator_id--fuel-readings"
                    onclick="tryItOut('POSTapi-v1-generators--generator_id--fuel-readings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-generators--generator_id--fuel-readings"
                    onclick="cancelTryOut('POSTapi-v1-generators--generator_id--fuel-readings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-generators--generator_id--fuel-readings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/generators/{generator_id}/fuel/readings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>tank_level_liters</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="tank_level_liters"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               value="21"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 99999.99. Example: <code>21</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>meter_hours</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="meter_hours"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               value="13"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 99999.99. Example: <code>13</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reading_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reading_date"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               value="2020-11-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date before or equal to <code>today</code>. Example: <code>2020-11-16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>attachments</code></b>&nbsp;&nbsp;
<small>file[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="attachments[0]"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               data-component="body">
        <input type="file" style="display: none"
               name="attachments[1]"                data-endpoint="POSTapi-v1-generators--generator_id--fuel-readings"
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 5120 kilobytes.</p>
        </div>
        </form>

                    <h2 id="alokod-GETapi-v1-generators--generator_id--fuel-consumption">GET api/v1/generators/{generator_id}/fuel/consumption</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--fuel-consumption">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/fuel/consumption" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"from\": \"2026-07-21T16:46:00\",
    \"to\": \"2020-11-16\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/fuel/consumption"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "from": "2026-07-21T16:46:00",
    "to": "2020-11-16"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--fuel-consumption">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--fuel-consumption" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--fuel-consumption"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--fuel-consumption"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--fuel-consumption" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--fuel-consumption">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--fuel-consumption" data-method="GET"
      data-path="api/v1/generators/{generator_id}/fuel/consumption"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--fuel-consumption', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--fuel-consumption"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--fuel-consumption');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--fuel-consumption"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--fuel-consumption');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--fuel-consumption"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/fuel/consumption</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--fuel-consumption"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--fuel-consumption"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--fuel-consumption"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="from"                data-endpoint="GETapi-v1-generators--generator_id--fuel-consumption"
               value="2026-07-21T16:46:00"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-07-21T16:46:00</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="to"                data-endpoint="GETapi-v1-generators--generator_id--fuel-consumption"
               value="2020-11-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>from</code>. Must be a date before or equal to <code>today</code>. Example: <code>2020-11-16</code></p>
        </div>
        </form>

                    <h2 id="alokod-GETapi-v1-generators--generator_id--fuel-cost-per-kwh">GET api/v1/generators/{generator_id}/fuel/cost-per-kwh</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--fuel-cost-per-kwh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/fuel/cost-per-kwh" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"from\": \"2026-07-21T16:46:00\",
    \"to\": \"2020-11-16\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/fuel/cost-per-kwh"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "from": "2026-07-21T16:46:00",
    "to": "2020-11-16"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--fuel-cost-per-kwh">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--fuel-cost-per-kwh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--fuel-cost-per-kwh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--fuel-cost-per-kwh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--fuel-cost-per-kwh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--fuel-cost-per-kwh" data-method="GET"
      data-path="api/v1/generators/{generator_id}/fuel/cost-per-kwh"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--fuel-cost-per-kwh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--fuel-cost-per-kwh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--fuel-cost-per-kwh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/fuel/cost-per-kwh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="from"                data-endpoint="GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
               value="2026-07-21T16:46:00"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-07-21T16:46:00</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="to"                data-endpoint="GETapi-v1-generators--generator_id--fuel-cost-per-kwh"
               value="2020-11-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>from</code>. Must be a date before or equal to <code>today</code>. Example: <code>2020-11-16</code></p>
        </div>
        </form>

                    <h2 id="alokod-GETapi-v1-generators--generator_id--fuel-status">GET api/v1/generators/{generator_id}/fuel/status</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-generators--generator_id--fuel-status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/generators/17/fuel/status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/generators/17/fuel/status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-generators--generator_id--fuel-status">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-generators--generator_id--fuel-status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-generators--generator_id--fuel-status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-generators--generator_id--fuel-status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-generators--generator_id--fuel-status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-generators--generator_id--fuel-status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-generators--generator_id--fuel-status" data-method="GET"
      data-path="api/v1/generators/{generator_id}/fuel/status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-generators--generator_id--fuel-status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-generators--generator_id--fuel-status"
                    onclick="tryItOut('GETapi-v1-generators--generator_id--fuel-status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-generators--generator_id--fuel-status"
                    onclick="cancelTryOut('GETapi-v1-generators--generator_id--fuel-status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-generators--generator_id--fuel-status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/generators/{generator_id}/fuel/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-generators--generator_id--fuel-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-generators--generator_id--fuel-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>generator_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="generator_id"                data-endpoint="GETapi-v1-generators--generator_id--fuel-status"
               value="17"
               data-component="url">
    <br>
<p>The ID of the generator. Example: <code>17</code></p>
            </div>
                    </form>

                <h1 id="aamolat-almns">عمولات المنصة</h1>

    

                                <h2 id="aamolat-almns-GETapi-v1-platform-commissions">GET api/v1/platform-commissions</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-platform-commissions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/platform-commissions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/platform-commissions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-platform-commissions">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-platform-commissions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-platform-commissions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-platform-commissions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-platform-commissions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-platform-commissions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-platform-commissions" data-method="GET"
      data-path="api/v1/platform-commissions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-platform-commissions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-platform-commissions"
                    onclick="tryItOut('GETapi-v1-platform-commissions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-platform-commissions"
                    onclick="cancelTryOut('GETapi-v1-platform-commissions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-platform-commissions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/platform-commissions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-platform-commissions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-platform-commissions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="aamolat-almns-GETapi-v1-platform-commissions-report-pdf">GET api/v1/platform-commissions/report-pdf</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-platform-commissions-report-pdf">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/platform-commissions/report-pdf" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/platform-commissions/report-pdf"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-platform-commissions-report-pdf">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-platform-commissions-report-pdf" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-platform-commissions-report-pdf"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-platform-commissions-report-pdf"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-platform-commissions-report-pdf" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-platform-commissions-report-pdf">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-platform-commissions-report-pdf" data-method="GET"
      data-path="api/v1/platform-commissions/report-pdf"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-platform-commissions-report-pdf', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-platform-commissions-report-pdf"
                    onclick="tryItOut('GETapi-v1-platform-commissions-report-pdf');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-platform-commissions-report-pdf"
                    onclick="cancelTryOut('GETapi-v1-platform-commissions-report-pdf');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-platform-commissions-report-pdf"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/platform-commissions/report-pdf</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-platform-commissions-report-pdf"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-platform-commissions-report-pdf"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="aamolat-almns-GETapi-v1-platform-commissions-export">GET api/v1/platform-commissions/export</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-platform-commissions-export">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/platform-commissions/export" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/platform-commissions/export"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-platform-commissions-export">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
vary: Origin
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;يجب تسجيل الدخول أولاً.&quot;,
    &quot;data&quot;: null,
    &quot;errors&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-platform-commissions-export" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-platform-commissions-export"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-platform-commissions-export"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-platform-commissions-export" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-platform-commissions-export">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-platform-commissions-export" data-method="GET"
      data-path="api/v1/platform-commissions/export"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-platform-commissions-export', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-platform-commissions-export"
                    onclick="tryItOut('GETapi-v1-platform-commissions-export');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-platform-commissions-export"
                    onclick="cancelTryOut('GETapi-v1-platform-commissions-export');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-platform-commissions-export"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/platform-commissions/export</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-platform-commissions-export"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-platform-commissions-export"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="aamolat-almns-PATCHapi-v1-platform-commissions--platform_commission_id--status">PATCH api/v1/platform-commissions/{platform_commission_id}/status</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-platform-commissions--platform_commission_id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/platform-commissions/17/status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"paid\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/platform-commissions/17/status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "paid"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-platform-commissions--platform_commission_id--status">
</span>
<span id="execution-results-PATCHapi-v1-platform-commissions--platform_commission_id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-platform-commissions--platform_commission_id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-platform-commissions--platform_commission_id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-platform-commissions--platform_commission_id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-platform-commissions--platform_commission_id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-platform-commissions--platform_commission_id--status" data-method="PATCH"
      data-path="api/v1/platform-commissions/{platform_commission_id}/status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-platform-commissions--platform_commission_id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-platform-commissions--platform_commission_id--status"
                    onclick="tryItOut('PATCHapi-v1-platform-commissions--platform_commission_id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-platform-commissions--platform_commission_id--status"
                    onclick="cancelTryOut('PATCHapi-v1-platform-commissions--platform_commission_id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-platform-commissions--platform_commission_id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/platform-commissions/{platform_commission_id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-platform-commissions--platform_commission_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-platform-commissions--platform_commission_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>platform_commission_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="platform_commission_id"                data-endpoint="PATCHapi-v1-platform-commissions--platform_commission_id--status"
               value="17"
               data-component="url">
    <br>
<p>The ID of the platform commission. Example: <code>17</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-platform-commissions--platform_commission_id--status"
               value="paid"
               data-component="body">
    <br>
<p>Example: <code>paid</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>paid</code></li></ul>
        </div>
        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
