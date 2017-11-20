This is a custom form module for module for the ExpressionEngine, which is built on the CodeIgniter framework.
Due to the great lack of form customisation and validation in ExpressionEngine, I set about to build my own CMS-able add-on for doing just that.
The features include a completely customisable set of validation rules, which can then be used on as many different forms as you wish and it can even build the entire form HTML for you.
This could mean that one template can contain a custom form tag, and the client can use it to create multiple forms for many different purposes, without any knowledge of HTML - the way a proper CMS system should work.

It allows for JQuery form validation and submission, but also uses server-side PHP validations for added security.

Form sumbissions are always added to the database, which is viewable through the CMS, but can also be sent via email to a recipient (or list of).
You can specify an ExpressionEngine template as the body for the email, allowing you to use channel entries and any other default tags to enhance it's look.

The forms can also accept file uploads, which are stored on the server to be viewed later. These will also be sent as attachments in the email, if one is sent out.

This module allows full customisation of as many different kinds of forms as you like, with as many different validation rules as you require.
