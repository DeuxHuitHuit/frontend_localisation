Frontend Localisation
==============

Offers an integrated solution to localise the Frontend of your site.

* Version: 1.3.1
* Build Date: 2012-01-08
* Authors:
	- [Xander Group](http://www.xanderadvertising.com)
	- Vlad Ghita
* Requirements:
	- Symphony 2.2 or above
	- At least one frontend language driver. @see **3 Installation**

Thank you all other Symphony & Extensions developers for your inspirational work.





## 0 @todo list ##

- add support for GNU PO and JAVA properties.
- implement `Add new item` | `Delete item` | `Item reordering (subgrouping)` features for the UI.





## 1 Synopsis ##

Frontend localisation in Symphony (and other systems) implies coverage of two problems:<br />
1. Frontend language detection and (optional) redirect mechanism.<br />
2. Translation mechanism of static text, whether it's a few words long or a few paragraphs.

For problem 1, there are a few extensions that provide this functionality, but there is a lack of unified approach. This extension is decoupled from any of these drivers and provides a mechanism to associate it with them. More details in **5.1 Frontend Language** section.<br />
For problem 2, this extension offers a translation mechanism for creating and editing Translation strings. More details in **5.2 Translation Manager** section.






## 2 Features ##
For site builders:

* Admin UI for Translations management (usable by Authors as well)
* changeble `Language Driver`
* changeble `Reference Language`
* Translations consolidation on uninstall
* one button update of all language Translations referencing `Reference Language`
* offers one Datasource with strings from all Translations attached to current page and one Datasource with current languages from Language Driver.

For PHP developers:

* unifies Frontend language drivers in one access point.






## 3 Installation ##

1. Make sure you have at least one language driver installed (= install that extension). Currently supported language drivers are: [Language Redirect](https://github.com/klaftertief/language_redirect).
1. Upload the `frontend_localisation` folder found in this archive to your Symphony `extensions` folder.    
2. Enable it by selecting `Frontend Localisation` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.






## 4 Upgrading ##

If you're upgrading from > 0.2beta to 1.0 or later, uncomment [these lines](https://github.com/vlad-ghita/frontend_localisation/blob/master/extension.driver.php#L363-365), go to Preferences page and push the `Convert Translations to 1.0` button. Comment the lines back and you're good to go.






## 5 Usage ##

### 5.1 Frontend Language ###



#### 5.1.1 @ Site builders ####

On Preferences page you can select:

- `Language Driver` you want to use from supported and integrated ones. The Language Driver provides Frontend language information for the system.
- `Reference Language` should be the main language of your site frontend.



#### 5.1.2 @ PHP developers ####

This extension provides a [FLang class](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FLang.php) implementing [Singleton interface](https://github.com/symphonycms/symphony-2/blob/master/symphony/lib/core/interface.singleton.php) for easy access to Frontend language information.

<b>Adding a Language Driver</b>

1. In [$supported_drivers](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FLang.php#L22) array add the driver. @see property description.
2. Create a new class named `FLDriver<driver_name>` that extends `FLDriver` class. Save it in file `/frontend_localisation/lib/class.FLDriver<driver_name>.php`.
3. Done. You can now select this new driver on Preferences page.
4. Don't forget to send a pull request if you want to share the change.


<br />
### 5.2 Translation Manager ###

#### 5.2.1 Preferences page ####

- `Default storage format` is used as default when creating new Translations.
- `Consolidate Translations` is set default to `checked`. When this is checked, on uninstall, translation folders will **not** be deleted.
- pushing `Update Translations` button will update all languages Translations with reference to `Reference Language`.

#### 5.2.2 Managing Translations ####

1. Add new translations through Admin interface.
2. Add new translation strings. @see **6 Examples**. I hope in future the Admin interface will supply the <b>create new item</b> functionality.
3. Add `FL: Translations` datasource to your pages. (visit `?debug` -> `/data/fl-translations`)
4. Add `fl_utilities.xsl` to your stylesheets. See the [utility](https://github.com/vlad-ghita/frontend_localisation/blob/master/utilities/fl_utilities.xsl) for usage.



## 6 Examples ##

Go to `Translations`. Hit the `Create New` button. Fill the `Handle`, `Storage format`, set `type` to `Normal` and select `Pages` where this Translation will be added to.

On pressing the `Create Translation` button, in every language folder from `workspace/translations` 2 new files will be created:

    <<handle>>.data.<<storage_format>> --- DATA file
    <<handle>>.meta.<<storage_format>> --- META file

Supported storage formats are:

    xml => XML
    po => GNU PO
    i18n => JAVA properties

`DATA file` contains business information. This will be edited.<br />
`META file` contains various info about the Translation (not of interest).

Now you must add your translation strings in `DATA file`.


<br />
### !!! **!!! Very important !!!** !!! ###

Let's say your `Reference language` from `System -> Preferences` is `English`. Edit the `<<handle>>.data.<<storage_format>>` file from `workspace/translations/en`.<br />
If it's `French`, edit the `workspace/translations/fr` files. Always!

On visiting in Admin the edit page of a Translation, the `DATA file` in all languages will be synchronised from the `Reference language`'s `DATA file`.

`Translation synchronisation` = From reference translation to target translation, copy all items from reference while preserving original values from target.

So, if your `Reference language` is `English` and you're creating items for `French`, on visiting translation page you **will** lose all content from French translation (English translation doesn't contain anything = copy `empty` to everywhere).


<br />
After adding the translation strings in proper file (from `Reference language`), visit the edit page for Translation and use the interface to translate the strings in all languages.


### 6.1 `Storage format` is XML ###

Created files will be:

    <<handle>>.data.xml
    <<handle>>.meta.xml

Here's an example from my `master.data.xml`:

    <?xml version="1.0" encoding="utf-8"?>
    <data>
        <item handle="site-name"><![CDATA[Xander Advertising]]></item>
        <item handle="read-more"><![CDATA[read more]]></item>
        <item handle="image"><![CDATA[Image]]></item>
        <item handle="language"><![CDATA[Language]]></item>
        <item handle="portfolio"><![CDATA[Portfolio]]></item>
        <admin>
            <item handle="edit"><![CDATA[edit]]></item>
            <item handle="entry"><![CDATA[Entry]]></item>
        </admin>
        <copyright>
            <item handle="message-p1"><![CDATA[All rights to the content of this website are reserved to Xander Advertising]]></item>
            <item handle="message-p2"><![CDATA[Copies of any kind obtained without prior permission are prohibited.]]></item>
        </copyright>
        <page-not-found>
            <item handle="title"><![CDATA[Page not found]]></item>
            <item handle="message"><![CDATA[Return to main pate in order to continue.]]></item>
        </page-not-found>
    </data>



<br />
### 6.2 `Storage format` is GNU PO ###

Created files will be:

    <<handle>>.data.po
    <<handle>>.meta.po

Here's an example from my `master.data.po`:

    To be continued ...



<br />
### 6.3 `Storage format` is Java properties ###

Created files will be:

    <<handle>>.data.i18n
    <<handle>>.meta.i18n

Here's an example from my `master.data.i18n`:

    To be continued ...
