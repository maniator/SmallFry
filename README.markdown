Small VC
==

###Controller:
Put all controllers in the `/controller` folder

An example of a controller called `Test`:

		class TestController extends AppController {
			var $name = 'Test';
			
			//pages go here
			function index(){
				self::set('test', 'hello there');
			}
		}
###Views:
Put all views in the `/view` folder with a directory for every different controller:

The view for the `Test` controller (located in `/views/test/index.stp`):

		echo $test;
		
This will display to the user when they go to `http://localhost/index.php/Test/index`:

		hello there

