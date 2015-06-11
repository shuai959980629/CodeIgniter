<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *
 * 帮助中心
 * @author zhoushuai
 */
class Help_Controller extends Common_Controller
{
    
    
    private $help ;
    private $content;
    public function __construct()
    {
        parent::__construct();
        require_once ROOT_PATH.'attachment/help.php';
        $this->help = $help;
        $this->content = $content;
        
    }
    /**
     * 帮助中心  
     */
    protected function help()
    {        
       return $this->help;
    }
    
    protected function content(){
        return $this->content;
    }


}
/* End of file Help_Controller.php */
