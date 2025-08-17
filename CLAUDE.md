# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Key Technologies

- **Backend**: Laravel 12 (framework version ^12.0)
- **Admin Panel**: Filament 4 (^4.0)
- **Frontend**: Vite + TailwindCSS 4 + Laravel Mix
- **Database**: MySQL, configurable for production
- **Testing**: PHPUnit 11.5.3
- **Code Quality**: Laravel Pint for PHP formatting

this project is the new version of old project

which is a wordpress project was using to creata a property crm

project path :  D:\Server\crm

- **url**: http://crm.test/
- **user**: admin
- **pass **: 123@alhiaa_admin

the project was build using acf pro and a child theme :D:\Server\crm\wp-content\themes\alhiaa-system

now you need to read the

@.docs/filament4.md

@.docs/laravel12.md

@.docs/system-dec.md

always dont do any task by your self

always ask subagent to do the task

how you START THAT ?
1- make a full detailed plan of it
2- imagine the senarios to test it
3- set the unit testing plan
4- set the integrated testing plan
5- GIVE THE subagent THE TASK WITH ALL THE LAST 4 DATA
6- also ask him to make testable code so after making any class , edit function you must run a  test script to test if this modification is working or not
7- after you finish the task , you MUST run all the tests of this task again if all success
8- do the finish process here @doc/finish.md after you finish every part you MUST import all the data from the old system , you can get the data using direct connection to the database or write a script to do that then run it


always use the filament commands to generate resources
1- create the database
2- don't create the resource files yourself , always use the filamment official command : you will find them in https://filamentphp.com/docs/4.x/resources/overview







