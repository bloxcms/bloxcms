<?php
/*
 * PHP template Engine.
 */
class Templater
{ 
    var $vars = [];
    var $template_dir;

    public function __construct()
    {
        $this->template_dir = Blox::info('templates', 'dir');
    }
    
    
    public function assign($tplVarName, $value) 
    {
        $this->vars[$tplVarName] = $value; 
    }


    public function fetch($tplFile) 
    {              
        foreach ($this->vars as $i => $value)
            $$i = $value;
        ob_start();
        $this->convertPath($tplFile);
        require $tplFile;
        $output = ob_get_clean();
        return $output;
    }


    public function display($tplFile) 
    {                                                                        
        echo $this->fetch($tplFile);
    }
    
    
    private function convertPath(&$tplFile)
    { 
        if (!strstr($tplFile, '/')) {   
            $tplFile = $this->template_dir."/".$tplFile;            
            return $tplFile;
        }     
    }
}        