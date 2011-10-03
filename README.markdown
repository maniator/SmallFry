SmallFry PHP Library
==

###Controller:
Put all controllers in the `/controller` folder

An example of a controller called `Test`:

		class TestController extends AppController {
			var $name = 'Test';
			
			//pages go here
			function index(){
				$this->set('test', 'hello there');
			}
		}
###Views:
Put all views in the `/view` folder with a directory for every different controller:

The view for the `Test` controller (located in `/view/test/index.stp`):

		echo $test;
		
This will display to the user when they go to `http://localhost/index.php/Test/index`:

		hello there

		
###MySQL:
Inside of the controller class you can do mysql queries  
The object to use is `$this->_mysql` which is a mysqli object with some extended functions.  

For example:

		class TestController extends AppController {
			var $name = 'Test';
			
			function index(){
				$rows = array();
				$result = $this->_mysql->run_query('SELECT * FROM TABLE');
				while($row = $this->_mysql->get_row($result)){
					$rows[] = $row;
				}
				$this->set('rows', $row); //for use in the view
			}
		}
