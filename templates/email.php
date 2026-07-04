<?php
// Simple email templates with {{placeholders}}
return [
    'request_review' => '<p>Request #{{id}} requires your review.</p><p><a href="{{link}}">Open request</a></p>',
    'request_approved' => '<p>Your request #{{id}} has been <strong>approved</strong>.</p>',
    'request_denied' => '<p>Your request #{{id}} has been <strong>denied</strong>.</p>'
];
