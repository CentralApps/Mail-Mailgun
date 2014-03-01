<?php
namespace CentralApps\MailGun;

class Configuration extends \CentralApps\Mail\Configuration
{
    public $api_key = "";
    public $domain = "";

    protected $campaign = null;
    protected $dkim = null;
    protected $test_mode = null;
    protected $tracking = null;
    protected $tracking_clicks = null;
    protected $tracking_opens = null;
}
