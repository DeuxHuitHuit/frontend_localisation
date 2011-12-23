Frontend Localisation
==============

Offers an integrated solution to localise the Frontend of your site.

* Version: 1.0
* Build Date: 2011-12-23
* Authors:
	- [Xander Group](http://www.xanderadvertising.com)
	- Vlad Ghita
* Requirements:
	- Symphony 2.2 or above
	- At least one frontend language driver. See **Adding a Language Driver** in **4.1 Frontend Language** section.

Thank you all other Symphony & Extensions developers for your inspirational work.




# 1 Synopsis #

Frontend localisation in Symphony (and other systems) implies coverage of two problems:<br />
1. Frontend language detection and (optional) redirect mechanism.<br />
2. Translation mechanism of static text, whether it's a few words long or a few paragraphs.

For problem 1, there are a few extensions that provide this functionality, but there is a lack of unified approach. This extension is decoupled from any of these drivers and provides a mechanism to associate it with them. More details in **4.1 Frontend Language** section.<br />
For problem 2, this extension offers a translation mechanism for creating and editing Translation strings. More details in **4.2 Translation Manager** section.



# 2 Features #
For site builders:

* Admin UI for Translations management
* changeble `Language Driver`
* changeble `Reference Language`
* Translations consolidation on uninstall
* one button update of all language Translations referencing `Reference Language`
* offers one Datasource with strings from all Translations attached to current page and one Datasource with current languages from Language Driver.

For PHP developers:

* unifies Frontend language drivers in one access point.



# 3 Installation #

1. Make sure you have at least one language driver installed. [Language Redirect](https://github.com/klaftertief/language_redirect), for example.
1. Upload the `frontend_localisation` folder found in this archive to your Symphony `extensions` folder.    
2. Enable it by selecting `Frontend Localisation` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.



# 4 Usage #

## 4.1 Frontend Language ##

### 4.1.1 @ PHP developers ###

This extension provides a [FLang class](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FLang.php) implementing [Singleton interface](https://github.com/symphonycms/symphony-2/blob/master/symphony/lib/core/interface.singleton.php) for easy access to Frontend language information.

<b>Adding a Language Driver</b>

1. In [$supported_drivers](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FLang.php#L22) array add the driver. @see property doscription.
2. Create a new class named `FLDriver<driver_name>` that extends `FLDriver` abstract class. Save it in file `/frontend_localisation/lib/class.FLDriver<driver_name>.php`.
3. For default Language Driver, you must install extension [Language Redirect](https://github.com/klaftertief/language_redirect) by Jonas Coch, at least version 1.0.2.
4. Done. You can now select this driver on Preferences page.



### 4.1.2 @ Site builders ###

On Preferences page you can select:

- `Language Driver` you want to use from supported and integrated ones. The Language Driver provides Frontend language information for the system.
- `Reference Language` should be the main language of your site.


## 4.2 Translation Manager ##

### 4.2.1 Preferences page ###

- `Default storage format` is used as default when creating new Translations.
- `Consolidate Translations` is set default to `checked`. When this is checked, on uninstall, translation folders will **not** be deleted.
- pushing `Update Translations` button will update all languages Translations with reference to `Reference Language`.


### 4.2.2 Managing Translations ###

1. Add new translations through Admin interface.
2. Add new translations for selected `$storage_format` (XML, PO, I18N) directly in the file from the filesystem at `[...]/workspace/translations`. Modify the `reference_language` translation. The correspondant files for the rest of languages will be automatically updated when you visit that Translation edit page in Admin or whe you push the `Update Translations` button on Preferences.<br />
I hope in the future the Admin interface wil supply the <b>create new item</b> functionality.
3. Add `FL: Translations` datasource to your pages.
4. Add `fl_utilities.xsl` to your stylesheets. See the utility for usage.




#5 Upgrading #

If you're upgrading from > 0.2beta to 1.0, uncomment [these lines](https://github.com/vlad-ghita/frontend_localisation/blob/master/extension.driver.php#L320-322), go to Preferences page and push the `Convert Translations to 1.0` button. Comment the lines back and you're good to go.



# 6 Compatibility #

         Symphony | Frontend Localisation
------------------|----------------
      2.0 — 2.1.* | Not compatible
      2.2 - *     | [latest](https://github.com/vlad-ghita/frontend_localisation)

Language Redirect | Frontend Localisation
------------------|----------------
        * — 1.0.1 | Not compatible
    1.0.2 - *     | [latest](https://github.com/vlad-ghita/frontend_localisation)



# 7 Changelog #

- 1.0 : 23 dec 2011
    * Initial release.