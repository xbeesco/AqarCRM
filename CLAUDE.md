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

@docs/filament4.md

@docs/laravel12.md

@docs/system-dec.md

always dont do any task by your self

always ask subagent to do the task

how you make any task

1- make a full detailed plan of it

2- imagine the senarios to test it

3- set the unit testing plan

4- set the integrated testing plan

5- always make ask the code to be testable so if the developem made a class , edit function you must run a  test script to test if this modification is working or not

6- after you finish the task , you MUST run all the tests of this task again if all success

do the finish process here @doc/finish.md

after you finish every part you MUST import all the data from the old system , you can get the data using direct connection to the database which its info in the wordpress project

be carefull alot of tables is useless and depricated , so before you detect from where to get the data you must check the files to determine which the correct data source

after every step also you MUST

1- write all the idea of senarios will face the user in this phase or the critical cases that perhaps face the user

2- create playwright tests for every case  and run it to success

again dont do any development tas by yourself , always delegeat it to sub agents
