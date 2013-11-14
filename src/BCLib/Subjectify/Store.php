<?php

namespace BCLib\Subjectify;

use BCLib\LCCallNumbers\LCCallNumber;

interface Store
{
    public function insertTopic(Topic $topic);
    public function insertLCCRange(LCCallNumber $low_lc, LCCallNumber $high_lc, Topic $topic);
    public function search(LCCallNumber $call_number);
}
