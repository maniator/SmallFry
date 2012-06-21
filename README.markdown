SmallFry PHP Library
==

###Configuration:
Put all configuration into in the `/smallFry/config/AppConfig.php` file and set ROOTs in the `Autoloader.php` file

To add something to the configuration you can do:

		$CONFIG->set('CONFIG_VARIABLE', 'VALUE');
Later in your app (when you are **not** inside of `AppConfig.php`) you can use that configuration variable by doing:

		$this->CONFIG->get('CONFIG_VARIABLE');

###Controller:
Put all controllers in the `/controller` folder

An example of a controller called `Test`:

		class TestController extends AppController {
			var $name = 'Test';
			
			//pages go here
			function index(){
				$this->template->set('test', 'hello there');
			}
		}
###Views:
Put all views in the `/view` folder with a directory for every different controller:

The view for the `Test` controller (located in `/view/test/index.stp`):

		echo $test;
		
This will display to the user when they go to `http://localhost/index.php/Test/index`:

		hello there

		 
###MySQL and Models:
Inside of the controller class you can do mysql queries  
The object to use is `$this->ModelName` which is the model that handles all database queries.

For example (a raw mysql example):

		class TestController extends AppController {
			var $name = 'Test';
			
			function index(){
				$rows = array();
				$results = $this->Test->queryit('SELECT * FROM TABLE'); // one way to use a model to query the database
				foreach($results as $row) {
					$rows[] = $row;
				}
				$this->template->set('rows', $rows); //for use in the view
			}
		}
		
In order to use the following example you have to create a `Model` that goes with the current controller in the `model` directory:

		class Test extends AppModel {

		}
		
This model will query from the `tests` table if one is **not** doing raw MySQL statements.

This example selects all records from the `tests` table in the database:

		class TestController extends AppController {
			var $name = 'Test';
			
			function index(){
				$rows = array();
				$results = $this->Test->selectAll();
				foreach($results as $row) {
					$rows[] = $row;
				}
				$this->template->set('rows', $rows); //for use in the view
			}
		}
