Frontend Localisation
==============

Offers an integrated solution to localise the Frontend of your site.


## 0 @todo list ##

- add support for GNU PO and JAVA properties.
- implement `Add new item` | `Delete item` | `Item reordering (subgrouping)` features for the UI.





## 1 Synopsis ##

This extension deals with 2 major requests regarding multilingual sites:

1. Frontend language management. More details in **5.1 Frontend Language** section.<br />
2. Scalable translation mechanism of static text, whether it's a few words long or a few paragraphs. More details in **5.2 Translation Manager** section.





## 2 Features ##
For site builders:

* Frontend Languages management.
* backend UI for Translations management (usable by Authors as well)
* Translations consolidation on uninstall (keeps files for removed languages)
* one button update of all language Translations referencing `Reference Language`
* one Datasource with Frontend Languages information
* one Datasource with strings from all Translations attached to current page
* one nice and handy [utility](https://github.com/vlad-ghita/frontend_localisation/blob/master/utilities/fl_utilities.xsl) tu rule them all. Read more in **6.4 XSL FLang utilities** section.

For PHP developers:

* easy access to Frontend Language information
* UI assets to build the language tabs





## 3 Installation ##

1. Make sure you have at least one FLang detection driver installed (= install that extension). For example, use [FLang detection gTLDs](https://github.com/vlad-ghita/flang_detection_gtlds).
1. Upload the `frontend_localisation` folder found in this archive to your Symphony `extensions` folder.    
2. Enable it by selecting `Frontend Localisation` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.





## 4 Upgrading ##

Keep your fingers crossed and push za button!




## 5 Usage ##

### 5.1 Frontend Language ###



#### 5.1.1 @ Site builders ####

On Preferences page you can:

- `Language codes`: insert multiple language codes for desired Frontend Languages
- `Main language`: chose the main language of the site



#### 5.1.2 @ PHP developers ####

This extension provides a static [FLang class](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.FLang.php) for easy access to Frontend language information.<br/>
If you want to create a third-party FLang detection driver, make sure that your driver adds the `fl-language` & `fl-region` params to the URL query string with appropriate values.



<br />
### 5.2 Translation Manager ###

#### 5.2.1 Preferences page ####

- `Reference language`: the reference point for Translations synchronisation
- `Default storage format`: is used as default when creating new Translations
- `Consolidate translations` is set default to `checked`. When this is checked, on uninstall, translation folders will **not** be deleted
- pushing `Update Translations` button will update all languages Translations with reference to `Reference Language`

#### 5.2.2 Managing Translations ####

1. Add new translations through Admin interface.
2. Add new translation strings. @see **6 Examples**. I hope in future the Admin interface will supply <b>CRUD</b> functionality for items.
3. Add `FL: Languages` datasource to your pages. (visit `?debug` -> `/data/fl-languages`)
4. Add `FL: Translations` datasource to your pages. (visit `?debug` -> `/data/fl-translations`)
5. Add `fl_utilities.xsl` to your stylesheets. See the [utility](https://github.com/vlad-ghita/frontend_localisation/blob/master/utilities/fl_utilities.xsl) for usage.



## 6 Examples ##

Go to `Translations`. Hit the `Create New` button. Fill the `Handle`, `Storage format`, set `type` to `Normal` and select `Pages` where this Translation will be added to.

On pressing the `Create Translation` button, in every language folder from `workspace/translations` 2 new files will be created:

    <<handle>>.data.<<storage_format>> --- DATA file
    <<handle>>.meta.<<storage_format>> --- META file

Supported storage formats are:

    xml => XML
    (@todo) po => GNU PO
    (@todo) i18n => JAVA properties

`DATA file` contains business information. This will be edited.<br />
`META file` contains various info about the Translation (not of interest).

Now you must add your translation strings in `DATA file`.


<br />
### !!! **!!! Very important !!!** !!! ###

Let's say your `Reference language` from `System -> Preferences` is `English`. Edit the `<<handle>>.data.<<storage_format>>` file from `workspace/translations/en`.<br />
If it's `French`, edit the `workspace/translations/fr` files. Always!

On visiting in Admin the edit page of a Translation, the `DATA file` in all languages will be synchronised from the `Reference language`'s `DATA file`.

`Translation synchronisation` = From reference translation to target translation, copy all items from reference while preserving only corresponding items from target (extra items in target are lost).

So, if your `Reference language` is `English` and you're creating items for `French`, on visiting translation page you **will** lose all content from French translation that doesn't exist in English translation (English translation doesn't contain anything => copy `empty` to everywhere).


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
            <item handle="title">Page not found</item>
            <item handle="message"><![CDATA[<p>Requested page was not found. Return %1$s to continue.</p>]]></item>
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


### 6.4 XSL FLang utilities ###

These utilities offer an easy way to access the translations provided by `FL: Translations` datasource:

- easy acccess to translation items using xPath like selectors
- placeholders replacement just like `sprintf()` in PHP. (`%1$s`, `%2$s` etc)

Read the docs from the [XSLT utility](https://github.com/vlad-ghita/frontend_localisation/blob/master/utilities/fl_utilities.xsl) for usage examples.
