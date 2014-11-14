<?php

use Myth\CLI;

class Forge extends \Myth\Controllers\CLIController {

    public function __construct()
    {
        parent::__construct();

        $this->load->config('forge');
    }

    //--------------------------------------------------------------------


    public function _remap($method, $params)
    {
        if (method_exists($this, $method))
        {
            call_user_func_array( [$this, $method], $params);
        }
        else
        {
            call_user_func_array( [$this, 'run'], $params);
        }
    }
    
    //--------------------------------------------------------------------

    /**
     * Overrides to implement dynamic description building based on
     * scanning the collections and grabbing the information from
     * 'forge.php' files.
     */
    public function index()
    {
        $collections = config_item('forge.collections');

        if (! is_array($collections) || ! count($collections) )
        {
            return CLI::error('No generator collections found.');
        }

        // We loop through each collection scanning
        // for any generator folders that have a
        // 'forge.php' file. For each one found
        // we build out another section in our help commands
        foreach ($collections as $alias => $path)
        {
            $path = rtrim($path, '/ ') .'/';
            $folders = scandir($path);

            $_descriptions = [];

            foreach ($folders as $dir)
            {
                if ($dir == '.' || $dir == '..' || ! is_file($path . $dir .'/forge.php'))
                {
                    continue;
                }

                include $path . $dir .'/forge.php';

                // Don't have valid arrays to work with? Move along...
                if (! isset($descriptions))
                {
                    log_message('debug', '[Forge] Invalid forge.php file at: '. $path . $dir .'/forge.php');
                    continue;
                }

                $_descriptions = array_merge($descriptions, $_descriptions);
            }

            CLI::new_line();
            CLI::write(ucwords( str_replace('_', ' ', $alias)) .' Collection');
            $this->sayDescriptions($_descriptions);
        }
    }

    //--------------------------------------------------------------------

    /**
     * The primary method that calls the correct generator and
     * makes it run.
     */
    public function run()
    {

    }

    //--------------------------------------------------------------------

    /**
     * Overrides CLIController's version to support searching our
     * collections for the help desription.
     *
     * @param null $method
     */
    public function longDescribeMethod($method=null)
    {
	    $collections = config_item('forge.collections');

	    if (! is_array($collections) || ! count($collections) )
	    {
		    return CLI::error('No generator collections found.');
	    }

	    // We loop through each collection scanning
	    // for any generator folders that have a
	    // 'forge.php' file. For each one found
	    // we build out another section in our help commands
	    foreach ($collections as $alias => $path)
	    {

		    $path = rtrim($path, '/ ') .'/';
		    $folders = scandir($path);

		    if (! $i = array_search(ucfirst($method), $folders))
		    {
			    continue;
		    }

		    $dir = $path . $folders[$i] .'/';

		    if (! is_file($dir .'/forge.php'))
		    {
			    CLI::error("The {$method} command does not have any cli help available.");
		    }

		    include $dir .'/forge.php';

		    // Don't have valid arrays to work with? Move along...
		    if (! isset($long_descriptions))
		    {
			    log_message('debug', '[Forge] Invalid forge.php file at: '. $dir .'/forge.php');
			    continue;
		    }

		    if (empty($long_descriptions[$method]))
		    {
			    return CLI::error("The {$method} command does not have an cli help available.");
		    }

		    CLI::new_line();
		    CLI::write( CLI::color(ucfirst($method) .' Help', 'yellow') );
		    return CLI::write( CLI::wrap($long_descriptions[$method], 75) );
	    }

	    // Still here?
	    CLI::error("No help found for command: {$method}");
    }

    //--------------------------------------------------------------------

}