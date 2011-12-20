Frontend Localisation
==============

Offers a frontend localisation mechanism using XML files.

* Version: 0.5 beta
* Build Date: 2011-12-20
* Authors:
	- [Xander Group](http://www.xandergroup.ro)
	- Vlad Ghita
* Requirements:
	- Symphony 2.2 or above
	- At least one frontend language driver. See **Adding a Language Driver** in **4.1 Frontend Language** section.

Thank you all other Symphony & Extensions developers for your inspirational work.


@todo - polish Frontend Language classes. They look ugly.
@todo - implementation for PO and I18N <br/>
@todo - per translation option for storage (convertion from one type to another)<br/>
@todo - rework of FL_Translations Datasource


## 1 Synopsis ##

Frontend localisation in Symphony (and other systems) implies coverage of two problems:<br />
1. Frontend language detection and (optional) redirect mechanism.<br />
2. Translation mechanism of static text, whether it's a few words long or a few paragraphs.<br />

For problem 1, there are a few extensions that provide this functionality, but there is a lack of unified approach. This extension is decoupled from any of these drivers and provides a mechanism to associate it with them. More details in **4.1 Frontend Language** section.<br />
For problem 2, this extension offers a translation mechanism using XML files. More details in **4.2 Translation Manager** section.



## 2 Features ##
For site builders:

* allows association of Pages to Translation files and vice-versa.
* allows editing of Translation Files in Admin
* changeble `Language Driver`
* changeble `Reference Language`
* Translations consolidation on uninstall
* one button update of all language Translations referencing `Reference Language`
* offers one Datasource with strings from all Translation Files attached to current page and one Datasource with current languages from Language Driver.

For PHP developers:

* unifies Frontend language drivers in one access point.



## 3 Installation ##

1. Make sure you have at least one language driver installed. [Language Redirect](https://github.com/klaftertief/language_redirect), for example.
1. Upload the `frontend_localisation` folder found in this archive to your Symphony `extensions` folder.    
2. Enable it by selecting `Frontend Localisation` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.



## 4 Usage ##

### 4.1 Frontend Language ###

#### 4.1.1 @ PHP developers ####

This extension provides a [FrontendLanguage class](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FrontendLanguage.php) implementing [Singleton interface](https://github.com/symphonycms/symphony-2/blob/master/symphony/lib/core/interface.singleton.php) for easy access to Frontend language information.

##### Adding a Language Driver #####

1. In [$supported_language_drivers](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FrontendLanguage.php#L20) array add a name for this language driver with `value` set to extension folder of the driver.
2. Create a new class named `LanguageDriver<driver_name>` that extends `LangaugeDriver` abstract class. Save it in file `/frontend_localisation/lib/class.LanguageDriver<driver_name>.php`. You must implement all abstract methods from [LangaugeDriver class](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.LanguageDriver.php)
3. For default Language Driver, you must install extension [Language Redirect](https://github.com/klaftertief/language_redirect) by Jonas Coch, at least version 1.0.2.
4. Done. You can now select this driver on Preferences page.


#### 4.1.2 @ Site builders ####

On Preferences page you can select:

- `Language Driver` you want to use from supported and integrated ones. The Language Driver provides Frontend language information for the system.
- `Reference Language` is the language code which Translations will be used as reference when updating other languages Translations.


### 4.2 Translation Manager ###

#### 4.2.1 Preferences page ####

- `Consolidate Translations` is set default to `checked`. When this is checked, on uninstall, translation folders will **not** be deleted.
- pushing `Update Translations` button will update all languages Translations with reference to `Reference Language`.


#### 4.2.2 Managing Translations ####

@to update




## 5 Compatibility ##

         Symphony | Frontend Localisation
------------------|----------------
      2.0 — 2.1.* | Not compatible
      2.2 - *     | [latest](https://github.com/vlad-ghita/frontend_localisation)

Language Redirect | Frontend Localisation
------------------|----------------
        * — 1.0.1 | Not compatible
    1.0.2 - *     | [latest](https://github.com/vlad-ghita/frontend_localisation)



## 6 Changelog ##

- to be released.