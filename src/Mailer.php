<?php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private static function renderTemplate($name, $data = []) {
        $tplFile = __DIR__ . '/../templates/email.php';
        if (!file_exists($tplFile)) return '';
        $templates = include $tplFile;
        if (!isset($templates[$name])) return '';
        $body = $templates[$name];
        foreach ($data as $k => $v) {
            $body = str_replace('{{'.$k.'}}', $v, $body);
        }
        return $body;
    }

    public static function send($to, $subject, $body, $template = null, $data = []) {
        $cfg = require __DIR__ . '/../config.php';
        $m = new PHPMailer(true);
        try {
            $m->isSMTP();
            $m->Host = $cfg['smtp']['host'];
            $m->Port = $cfg['smtp']['port'];
            if ($cfg['smtp']['username']) {
                $m->SMTPAuth = true;
                $m->Username = $cfg['smtp']['username'];
                $m->Password = $cfg['smtp']['password'];
            }
            $m->setFrom($cfg['smtp']['from_email'],$cfg['smtp']['from_name']);
            $m->addAddress($to);
            $m->isHTML(true);
            $m->Subject = $subject;
            if ($template) {
                $bodyRendered = self::renderTemplate($template, $data);
                if ($bodyRendered) $m->Body = $bodyRendered;
                else $m->Body = $body;
            } else {
                $m->Body = $body;
            }
            $m->send();
            return true;
        } catch (Exception $e) {
            AuditLogger::log('mail_error', null, $e->getMessage());
            return false;
        }
    }
}
