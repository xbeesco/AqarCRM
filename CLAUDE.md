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
@.docs/implementation-plan.md
@.docs/files-tree.json
@.docs/schema.json

always don't do any task by your self : always ask subagent to do the task, you are the teck team lead and you need to focus on this only


how to prepare for the project 
first you will need to create a script to fill .docs/files-tree.json file
folder_relative_path
	-file_relative_path_from_the_folder
		- summurized :  true or false
		- content
			- file use
			- functions[] the functions or the method in clss if it was a class

how a task work ?
1- detect what the next module (prperties , contracts , payments ) that the agent will work on 
2 - you ask a new agent to read files:  tree.json and implementation-plan to create a .doc/modules/module-slug.json file the 100% exaclty schema.json filled
3- ask the agent to follow the .doc/modules/module-slug.json exactly 100% and do all the tests
4- make a git commit with a shot sentence 


always make the table filters above the table https://filamentphp.com/docs/4.x/tables/filters/layout#displaying-filters-above-the-table-content


عملية الاختبارات الخاصه ب playwright يجب ان تغطي كل الشاشات الخاصه بال resource  
عملية الاختبارات الخاصه ب playwright يجب ان تغطي كل الحالات ال حرجة 
عملية الاختبارات الخاصه ب playwright يجب ان تغطي كل  عمليات الداخليه بحيث نختبر ما سيواجهه المستخدم في العملايت المعقدة


كل صفحة تدخلها تسحب محتوي الداخلي للصفحة و تتاكد انه لا يحتوي علي اخطاء برمجية 
يمكن ان تقابل بعض الصفحات الخاصه 
مثل صفحة فاتورة مدوفع او شيئ من هذا القبيل يجب ايضا ان تخنبرخها بنفس الاسلوب 

في حالة احتاجت ان تكتب اختبارات للتجريب اي شيئ اثناء البرمجة ( جزا من عملية الاختبار ) او كتابه او script  لتنفيذ شيئ معين , اجعله في مجلد tests\development لا تضيف اي ملفات في المجلد العام للمشروع الا للضرورة القصوي فقط
