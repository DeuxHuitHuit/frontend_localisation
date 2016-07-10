Frontend Localisation
==============



## 1 Synopsis ##

Offers the language tools to localise the Frontend of your site.




## 2 Features ##

For site builders:

* Frontend Languages management.
* one Datasource with Frontend Languages information

For PHP developers:

* easy access to Frontend Language information
* UI assets to build the language tabs




## 3 Installation ##

1. Make sure you have [Languages](https://github.com/vlad-ghita/languages) extension installed.
2. Make sure you have at least one FLang detection driver installed (= install that extension). For example, use [FLang detection gTLDs](https://github.com/vlad-ghita/flang_detection_gtlds).
3. Upload the `frontend_localisation` folder found in this archive to your Symphony `extensions` folder.    
4. Enable it by selecting `Frontend Localisation` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.





## 4 Upgrading ##

Keep your fingers crossed and push za button!




## 5 Usage ##

#### 5.1 @ Site builders ####

Attach `FL: Languages` datasource to your pages.

On Preferences page you can:

- `Site languages`: choose site languages
- `Main language`: choose the main language of the site

#### 5.2 @ PHP developers ####

This extension provides a static [FLang class](https://github.com/vlad-ghita/frontend_localisation/blob/master/lib/class.flang.php) for easy access to Frontend language information.<br/>
If you want to create a third-party FLang detection driver, make sure that your driver adds the `fl-language` & `fl-region` params to the URL query string with appropriate values.
You can also set a main region value, but you need to edit the config.php file manually. Doing so will force you to use a driver that specify a region.
