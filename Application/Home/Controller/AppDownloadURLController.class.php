<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class AppDownloadURLController extends GlobalController {

    public function skipDownloadURL(){
        $this->display('downLoad');
    }
    
}

?>