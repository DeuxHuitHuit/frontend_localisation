Frontend Localisation
==============

Offers a frontend localisation mechanism using XML files.

* Version: 0.1 beta
* Build Date: 2011-10-22
* Authors:
	- [Xander Group](www.xandergroup.ro)
	- Vlad Ghita
* Requirements:
	- Symphony 2.2 or above
	- At least one frontend language driver. See **Supported Language Drivers** in **Frontend Language** section.
	- Extension [Language Redirect](https://github.com/klaftertief/language_redirect) by Jonas Coch, at least version 1.0.2.

Thank you all other Symphony & Extensions developers for your inspirational work.



## Synopsis ##

Frontend localisation in Symphony (and other systems) implies coverage of two problems:<br />
1. Frontend language detection and (optional) redirect mechanism.<br />
2. Translation mechanism of static text, whether it's a few words long or a few paragraphs.<br />

For problem 1, there are a few extensions that provide this functionality, but there is a lack of a unified approach. This extension is decoupled from any of these drivers and provides a mechanism to associate it with them. More details in **Frontend Language** section.<br />
For problem 2, this extension offers a translation mechanism using XML files. More details in **Translation Manager** section.



## Features ##
For site builders:

* allows association of Pages to Translation files and vice-versa.
* @todo allows editing of Translation Files in Admin
* changeble `Language Driver`
* changeble `Reference Language`
* @todo changeble `Translation Path`
* changeable `Page name prefix` to distinguish Pages' Translation Files from other Translation Files.
* translations consolidation on unsinstall
* one button update of all language translations referencing `Reference Language`
* offers a Datasource with strings from all Translation Files attached to current page. 

For PHP developers:

* unifies Frontend language drivers in one access point.



## Installation ##

1. Make sure you have at least one language driver installed. [Language Redirect](https://github.com/klaftertief/language_redirect), for example.
1. Upload the `frontend_localisation` folder found in this archive to your Symphony `extensions` folder.    
2. Enable it by selecting `Frontend Localisation` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.



## Usage ##

### Frontend Language ###

### Translation Manager ###



## Compatibility ##

         Symphony | Frontend Localisation
------------------|----------------
      2.0 — 2.1.* | Not compatible
      2.2 - *     | [latest](https://github.com/vlad-ghita/frontend_localisation)

Language Redirect | Frontend Localisation
------------------|----------------
        * — 1.0.1 | Not compatible
    1.0.2 - *     | [latest](https://github.com/vlad-ghita/frontend_localisation)



## Changelog ##

* 0.1beta, 22 October 2011
	* initial beta release.
