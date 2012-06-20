----------------------------------------------------
GreenForm 1.1
----------------------------------------------------
Description here


----------------------------------------------------
Template Usage
----------------------------------------------------

<h1>Green Form</h1>
{exp:greenform:create_form return="return_url"}
	
	<label>Field</label>
	<input type="text" name="field" />
	<input type="submit" value="submit" />
	
	{errors}
	
{/exp:greenform:create_form}

-----------------------------------------------------
Features
-----------------------------------------------------
- Add your own fields
- Submitted forms are stored in the Database and (optionally) emailed out
- Advanced form validation using JQuery
