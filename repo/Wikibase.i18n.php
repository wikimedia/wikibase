<?php
/**
 * Internationalization file for the Wikibase extension.
 *
 * @since 0.1
 *
 * @file Wikibase.i18n.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */

$messages = array();

/** English
 * @author Daniel Kinzler
 * @author Tobias Gritschacher
 * @author John Erling Blad
 */
$messages['en'] = array(
	'wikibase-desc' => 'Structured data repository',
	'wikibase-edit' => 'edit',
	'wikibase-save' => 'save',
	'wikibase-cancel' => 'cancel',
	'wikibase-add' => 'add',
	'wikibase-save-inprogress' => 'Saving…',
	'wikibase-remove-inprogress' => 'Removing…',
	'wikibase-label-edit-placeholder' => 'enter label',
	'wikibase-description-edit-placeholder' => 'enter description',
	'wikibase-move-error' => 'You cannot move pages that are in the data namespace, and you cannot move pages into it.',
	'wikibase-sitelink-site-edit-placeholder' => 'specify site',
	'wikibase-sitelink-page-edit-placeholder' => 'specify page',
	'wikibase-label-input-help-message' => 'Enter the title of this data set in $1.',
	'wikibase-description-input-help-message' => 'Enter a short description in $1.',
	'wikibase-sitelinks' => 'List of pages linked to this item',
	'wikibase-sitelinks-add' => 'add a link to a site-link',
	'wikibase-sitelinks-empty' => 'No site-link for this item yet.',
	'wikibase-sitelinks-input-help-message' => 'Set a link to a page related to this item.',
	'wikibase-remove' => 'remove',
	'wikibase-propertyedittool-full' => 'List of values is complete.',
	'wikibase-propertyedittool-counter' => '($1 {{PLURAL:$1|entry|entries}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|entry|entries}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|One value|$1 values}} not saved yet',
	'wikibase-sitelinksedittool-full' => 'Links to pages are already set for all known sites.',
	'wikibase-disambiguation-title' => 'Disambiguation for "$1"',

	'wikibase-tooltip-error-details' => 'Details',
	'wikibase-error-save-generic' => 'An error occurred while trying to perform save, and because of this changes could not be completed.',
	'wikibase-error-remove-generic' => 'An error occurred while trying to perform remove, and because of this your changes could not be completed.',
	'wikibase-error-save-connection' => 'A connection error has occurred while trying to perform save, and because of this your changes could not be completed. Please check your internet connection.',
	'wikibase-error-remove-connection' => 'A connection error occurred while trying to perform remove, and because of this your changes could not be completed. Please check your internet connection.',
	'wikibase-error-save-timeout' => 'We are experiencing technical difficulties, and because of this your "save" could not be completed.',
	'wikibase-error-remove-timeout' => 'We are experiencing technical difficulties, and because of this your "remove" could not be completed.',
	'wikibase-error-autocomplete-connection' => 'Could not query Wikipedia API. Please try again later.',
	'wikibase-error-autocomplete-response' => 'Server responded: $1',

	'wikibase-setting-languages' => 'Additional languages<br />(as fallback when displaying data not available in the main language)',

	// Special pages
	'special-itembytitle' => 'Item by title',
	'special-itembylabel' => 'Item by label',
	'special-createitem' => 'Create a new item',

	// API errors
	'wikibase-api-aliases-invalid-list' => 'Use either one of "set", "add" and "remove" parameters.',
	'wikibase-api-no-token' => 'There are no token given.',
	'wikibase-api-no-data' => 'There are no data to operate upon.',
	'wikibase-api-cant-edit' => 'The logged in user is not allowed to edit.',
	'wikibase-api-no-permissions' => 'The logged in user does not have sufficient rights.',
	'wikibase-api-id-xor-wikititle' => 'Either provide the item "id" or pairs of "site" and "title" for a corresponding page.',
	'wikibase-api-no-such-item' => 'Could not find an existing item.',
	'wikibase-api-no-such-item-id' => 'Could not find an existing item for this id.',
	'wikibase-api-link-exists' => 'An article on the specified wiki is already linked.',
	'wikibase-api-add-with-id' => 'Cannot add with the id of an existing item.',
	'wikibase-api-add-exists' => 'Cannot add to an existing item.',
	'wikibase-api-update-without-id' => 'Update without a previous id is not possible.',
	'wikibase-api-no-such-item-link' => 'Could not find an existing item for this link.',
	'wikibase-api-create-failed' => 'Attempted creation of a new item failed.',
	'wikibase-api-modify-failed' => 'Attempted modification of the item failed.',
	'wikibase-api-save-failed' => 'Attempted save of the item failed.',
	'wikibase-api-invalid-contentmodel' => 'The content model for the page is invalid.',
	'wikibase-api-alias-incomplete' => 'Cannot find a definition of the alias for the item.',
	'wikibase-api-alias-not-found' => 'Cannot find any previous alias in the item.',
	'wikibase-api-alias-found' => 'Found a previous alias in the item.',
	'wikibase-api-not-recognized' => 'Directive is not recognized.',
	'wikibase-api-label-or-description' => 'Use either or both of "label" and "description".',
	'wikibase-api-label-not-found' => 'Cannot find a previous label for this language in the item.',
	'wikibase-api-description-not-found' => 'Cannot find a previous description for this language in the item.',
	'wikibase-api-wrong-class' => 'The content on the found page is not of correct type.', //'wikibase-api-invalid-contentmodel'

	//content model names
	'content-model-1001' => 'Wikibase item',
);

/** Message documentation (Message documentation)
 * @author Jeblad
 * @author Siebrand
 */
$messages['qqq'] = array(
	'wikibase-desc' => '{{desc}} See also [[m:Wikidata/Glossary#Wikidata|Wikidata]].',
	'wikibase-edit' => '[[File:Screenshot WikidataRepo 2012-05-13 F.png|right|0x150px]]
[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
This is a generic text used for a link (fig. 1 and 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that puts the user interface into edit mode for an existing element of some kind.',
	'wikibase-save' => '[[File:Screenshot WikidataRepo 2012-05-13 G.png|right|0x150px]]
This is a generic text used for a link (fig. 2 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that saves what the user has done while the user interface has been in edit mode.',
	'wikibase-cancel' => '[[File:Screenshot WikidataRepo 2012-05-13 G.png|right|0x150px]]
This is a generic text used for a link (fig. 2 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that cancels what the user has done while the user interface has been in edit mode.',
	'wikibase-add' => '[[File:Screenshot WikidataRepo 2012-05-13 F.png|right|0x150px]]
[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
This is a generic text used for a link (fig. 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that puts the user interface into edit mode for an additional element of some kind.',
	'wikibase-label-edit-placeholder' => '[[File:Screenshot WikidataRepo 2012-05-13 G.png|right|0x150px]]
This is a generic text used as a placeholder while editing a new label. See also Wikidatas glossary on [[m:Wikidata/Glossary#languageattribute-label|label]].',
	'wikibase-description-edit-placeholder' => '[[File:Screenshot WikidataRepo 2012-05-13 G.png|right|0x150px]]
This is a generic text used as a placeholder while editing a new description. See also Wikidatas glossary on [[m:Wikidata/Glossary#languageattribute-description|description]].',
	'wikibase-sitelink-site-edit-placeholder' => '[[File:Screenshot WikidataRepo 2012-05-13 E.png|right|0x150px]]
This is a generic text used as a placeholder while defining the site for a new sitelink. See also Wikidatas glossary on [[m:Wikidata/Glossary#sitelink|sitelink]].',
	'wikibase-sitelink-page-edit-placeholder' => '[[File:Screenshot WikidataRepo 2012-05-13 E.png|right|0x150px]]
This is a generic text used as a placeholder while defining the page for a possibly new sitelink. See also Wikidatas glossary on [[m:Wikidata/Glossary#sitelink|sitelink]].',
	'wikibase-label-input-help-message' => '[[File:Screenshot WikidataRepo 2012-05-13 I.png|right|0x150px]]
Bubble help message for entering the label of the data set used for a specific item. Takes on additional argument that is the sub site identifier, ie. "English" in nominative singular form. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-label|label]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-description-input-help-message' => '[[File:Screenshot WikidataRepo 2012-05-13 H.png|right|0x150px]]
Bubble help message for entering the description of the data set used for a specific item. Takes on additional argument that is the sub site identifier, ie. "English" in nominative singular form. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-description|description]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-sitelinks' => '[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
Header messages for pages on a specific cluster of sites linked to this item. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-sitelinks-add' => 'Add a sitelink to a language specific page on the cluster. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]].',
	'wikibase-sitelinks-empty' => 'There are no sitelinks for any of the language specific pages on the given cluster.  See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#sitelinks-title|title]].',
	'wikibase-sitelinks-input-help-message' => '[[File:Screenshot WikidataRepo 2012-05-13 D.png|right|0x150px]]
Bubble help message to set a sitelink to a language specific page on a given cluster. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#sitelinks-title|title]].',
	'wikibase-remove' => '[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
This is a generic text used for a link (fig. 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that removes an element of some kind, without the the user interface is put in edit mode.',
	'wikibase-propertyedittool-full' => 'A list of elements the user is assumed to enter is now complete.',
	'wikibase-propertyedittool-counter' => 'Parameters:
* $1 is the sum of elements in the list currently.',
	'wikibase-propertyedittool-counter-pending' => 'Parameters:
* $1 is the sum of elements in the list plus the ones pending (still in edit mode and not saved).
* $2 is the number of elements stored in the list (not pending).
* $3 is the message "wikibase-propertyedittool-counter-pending-pendingsubpart" with some additional markup around, expressing how many entries in the list are pending right now.',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => 'the number of pending elements within the list of site links and a leading "+". This will be inserted into parameter $3 of {{msg-mw|wikibase-propertyedittool-counter-pending}}.',
	'wikibase-sitelinksedittool-full' => 'The list of elements the user can enter is exhausted and there are no additional sites available. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]].',
	'wikibase-disambiguation-title' => 'Disambiguation page title. $1 is the label of the item being disambiguated.',
	'wikibase-tooltip-error-details' => 'Link within an error tooltip that will unfold additional information regarding the error (i.e. the mor specific error message returned from the underlying API).',
	'wikibase-error-save-generic' => 'Generic error message for an error happening during a save operation.',
	'wikibase-error-remove-generic' => 'Generic error message for an error happening during a remove operation',
	'wikibase-error-save-connection' => 'Error message for an error happening during a save operation. The error might most likely be caused by a connection problem.',
	'wikibase-error-remove-connection' => 'Error message for an error happening during a remove operation. The error might most likely be caused by a connection problem.',
	'wikibase-error-save-timeout' => 'Error message for an error happening during a save operation. The error was caused by a request time out.',
	'wikibase-error-remove-timeout' => 'Error message for an error happening during a remove operation. The error was caused by a request time out.',
	'wikibase-error-autocomplete-connection' => 'Error message for page auto-complete input box; displayed when API could not be reached.',
	'wikibase-error-autocomplete-response' => 'When querying the API for auto-completion fails, this message contains more detailed information about the error. $1 is the actual server error response or jQuery error code (e.g. when the server did not respond).',
	'wikibase-setting-languages' => 'Label for the user settings where the user can choose several languages he is interested in editing data for or which are interesting as a fallback when displaying data not available in the users main language',
	'special-itembytitle' => 'The item is identified through use of the title alone and must be disambiguated as there might be several sites that uses the same title for pages. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks-title|title]] and [[m:Wikidata/Glossary#Sitelinks-site|site]].',
	'special-itembylabel' => 'The item is identified through use of the label alone and must be disambiguated as there might be several entities that uses the same label for items. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-label|label]] and [[m:Wikidata/Glossary#Items|items]].',
	'wikibase-api-aliases-invalid-list' => 'This is an error message for a situation where the arguments to the API is inconsistent. Usually this should never be shown to the user, unless there are som exceptional error condition.',
	'wikibase-api-no-token' => 'This is an error message for a situation where there are no token given in the API call and it is expected. Usually this should never be shown to the user, unless there are som exceptional error condition. The message can be shown after misconfiguration of the system.',
	'wikibase-api-no-data' => 'This is an error message for a situation where the "data" argument to the API is lacking content. Usually this should never be shown to the user, unless there are som exceptional error condition. This message should probably not exist in the final version.',
	'wikibase-api-cant-edit' => 'This is an error message for a situation where the user is blocked from editing. This will be shown to the user if he tries to edit when being blocked.',
	'wikibase-api-no-permissions' => 'This is an error message for a situation where the user does not have sufficient rights. This will be shown to the user if the wiki uses group rights, and the user does not belong to the correct group, or if the rights of some other reason does not include the user.',
	'wikibase-api-id-xor-wikititle' => 'This is an error message for a situation where the arguments to the API is inconsistent. Usually this should never be shown to the user, unless there are som exceptional error condition.',
	'wikibase-api-no-such-item' => 'This is an error message for a situation where the API could not find an item, usually on an already found page. Usually this should never be shown to the user, unless there are som exceptional error condition, or the item is deleted due to a race condition.',
	'wikibase-api-no-such-item-id' => 'This is an error message for a situation where the API could not find an item by using a specific item id. Usually this should never be shown to the user, unless there are som exceptional error condition.',
	'wikibase-api-link-exists' => 'This is an error message for a situation where the arguments to the API requests a sitelink to be created but there already exist a similar link. Usually this should never be shown to the user, unless there are som exceptional error condition, or the link is already created due to a race condition.',
	'wikibase-api-add-with-id' => 'This is an error message for a situation where the arguments to the API includes an id, but the operation cannot be fullfilled because this creates a conflict. Usually this should never be shown to the user, unless there are som exceptional error condition.',
	'wikibase-api-add-exists' => 'This is an error message for a situation where the API already found an item, but the operation cannot be fullfilled because this creates a conflict. Usually this should never be shown to the user, unless there are som exceptional error condition, or an item is already created due to a race condition.',
	'wikibase-api-update-without-id' => 'This is an error message for an update where the API expect to have an id, but none are found. Usually this should never be shown to the user, unless there are som exceptional error condition.',
	'wikibase-api-no-such-item-link' => 'This is an error message for a situation where the API could not find an item given the specific sitelink. This message will be shown to the user if he manually specifies an invalid title.',
	'wikibase-api-create-failed' => 'This is an error message for a situation where the API tries to create an item but the operation cannot be completed. Usually this should never be shown to the user, unless there are som exceptional error condition, or the database is in maintenence mode.',
	'wikibase-api-modify-failed' => 'This is an error message for a situation where the API tries to modify an item but the operation cannot be completed. Usually this should never be shown to the user, unless there are som exceptional error condition, or the database is in maintenance mode.',
	'wikibase-api-save-failed' => 'This is an error message for a situation where the API tries to save an item but the operation cannot be completed. Usually this should never be shown to the user, unless there are som exceptional error condition, or the database is in maintenance mode.',
	'wikibase-api-invalid-contentmodel' => 'This is an error message for a situation where the API somehow requested content from a page but found the contentmodel to be inconsistent with the expected one. Usually this should never be shown to the user, unless there are som exceptional error condition, or an old database is not updated correctly.',
	'wikibase-api-alias-incomplete' => 'This is an error message for a situation where the API but somehow the alias is incomplete. Usually this should never be shown to the user, unless there are som exceptional error condition.',
	'wikibase-api-alias-not-found' => 'This is an error message for a situation where the API expects to find a previous alias but cannot find any. Usually this should never be shown to the user, unless there are som exceptional error condition, or there is a race condition during delete of the alias.',
	'wikibase-api-alias-found' => 'This is an error message for a situation where the API expects to find no previous alias but still finds one. Usually this should never be shown to the user, unless there are som exceptional error condition, or there is a race condition during creation of the alias.',
	'wikibase-api-not-recognized' => 'This is an error message displayed when the API somehow falls through a list of test for what to do, and fails to find any valid operation. This is only displayed when there is an exceptional error condition.',
	'wikibase-api-label-or-description' => 'This is an error message for a situation where the arguments to the API is inconsistent, either or both of "label" or "description" should be used but not none of them. Usually this should never be shown to the user, unless there are som exceptional error condition.',
	'wikibase-api-label-not-found' => 'This is an error message for a situation where the API expects to find a label but none are found. Usually this should never be shown to the user, unless there are som exceptional error condition, or there is a race condition during delete of the label.',
	'wikibase-api-description-not-found' => 'This is an error message for a situation where the API expects to find a label but none are found. Usually this should never be shown to the user, unless there are som exceptional error condition, or there is a race condition during delete of the description.',
	'wikibase-api-wrong-class' => 'This is an error message for a situation where the API expects to find a specific class or decendent thereof, but finds something else. Usually this should newer be shown to the user, unless there are some exceptional error condition, for example that the data integrity is lost.',
	'content-model-1001' => 'The name for Wikibase item content model, used when describing what type of content a page contains.',
);

/** Belarusian (Taraškievica orthography) (‪Беларуская (тарашкевіца)‬)
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'wikibase-desc' => 'Сховішча структураваных зьвестак',
	'wikibase-edit' => 'рэдагаваць',
	'wikibase-save' => 'захаваць',
	'wikibase-cancel' => 'скасаваць',
	'wikibase-add' => 'дадаць',
	'wikibase-label-edit-placeholder' => 'увядзіце метку',
	'wikibase-description-edit-placeholder' => 'увядзіце апісаньне',
	'wikibase-sitelink-site-edit-placeholder' => 'пазначце сайт',
	'wikibase-sitelink-page-edit-placeholder' => 'пазначце старонку',
	'wikibase-label-input-help-message' => 'Увядзіце назву гэтага набору зьвестак у $1.',
	'wikibase-description-input-help-message' => 'Увядзіце кароткае апісаньне ў $1.',
	'wikibase-sitelinks' => 'Старонкі Вікіпэдыі, што спасылаюцца на гэты аб’ект',
	'wikibase-sitelinks-add' => 'дадаць спасылку да старонкі Вікіпэдыі',
	'wikibase-sitelinks-empty' => 'Ніводная старонка Вікіпэдыі дагэтуль не спасылаецца на аб’ект.',
	'wikibase-sitelinks-input-help-message' => 'Дадайце спасылку на артыкул у Вікіпэдыі.',
	'wikibase-remove' => 'выдаліць',
	'wikibase-propertyedittool-full' => 'Сьпіс значэньняў выкананы.',
	'wikibase-sitelinksedittool-full' => 'Спасылкі на старонкі ёсьць ужо для ўсіх вядомых сайтаў.',
	'special-itembytitle' => 'Аб’ект паводле назвы',
	'special-itembylabel' => 'Аб’ект паводле меткі',
);

/** German (Deutsch)
 * @author Kghbln
 * @author Metalhead64
 */
$messages['de'] = array(
	'wikibase-desc' => 'Ermöglicht ein Repositorium strukturierter Daten',
	'wikibase-edit' => 'bearbeiten',
	'wikibase-save' => 'speichern',
	'wikibase-cancel' => 'abbrechen',
	'wikibase-add' => 'hinzufügen',
	'wikibase-save-inprogress' => 'Speichere ...',
	'wikibase-remove-inprogress' => 'Entferne ...',
	'wikibase-label-edit-placeholder' => 'Bezeichnung eingeben',
	'wikibase-description-edit-placeholder' => 'Beschreibung eingeben',
	'wikibase-move-error' => 'Du kannst keine Seiten aus dem Datennamensraum heraus- bzw. in ihn hineinverschieben.',
	'wikibase-sitelink-site-edit-placeholder' => 'Website angeben',
	'wikibase-sitelink-page-edit-placeholder' => 'Seite angeben',
	'wikibase-label-input-help-message' => 'Gib den Namen für diesen Datensatz in $1 an.',
	'wikibase-description-input-help-message' => 'Gib eine kurze Beschreibung in $1 an.',
	'wikibase-sitelinks' => '{{SITENAME}}-Seiten, die mit diesem Datenelement verknüpft sind',
	'wikibase-sitelinks-add' => 'füge eine Verknüpfung zu einer {{SITENAME}}-Seite hinzu',
	'wikibase-sitelinks-empty' => 'Bislang sind keine {{SITENAME}}-Seiten mit diesem Datenelement verknüpft.',
	'wikibase-sitelinks-input-help-message' => 'Leg eine Verknüpfung zu einer {{SITENAME}}-Seite fest.',
	'wikibase-remove' => 'entfernen',
	'wikibase-propertyedittool-full' => 'Die Werteliste ist vollständig.',
	'wikibase-propertyedittool-counter' => '({{PLURAL:$1|Ein Eintrag|$1 Einträge}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|Eintrag|Einträge}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Ein Wert wurde|$1 Werte wurden}} noch nicht gespeichert',
	'wikibase-sitelinksedittool-full' => 'Für alle bekannten Websites sind die Links auf die Seiten bereits festgelegt.',
	'wikibase-disambiguation-title' => 'Begriffsklärung für „$1“',
	'wikibase-tooltip-error-details' => 'Einzelheiten',
	'wikibase-error-save-generic' => 'Beim Versuch deine Änderungen zu speichern, ist ein Fehler aufgetreten. Deine Änderungen konnten daher nicht gespeichert werden.',
	'wikibase-error-remove-generic' => 'Beim Versuch das Entfernen auszuführen, ist ein Fehler aufgetreten.',
	'wikibase-error-save-connection' => 'Deine Änderungen konnten nicht gespeichert werden. Bitte prüfe deine Internetverbindung.',
	'wikibase-error-remove-connection' => 'Beim Versuch das Entfernen auszuführen, ist ein Fehler aufgetreten. Bitte prüfe deine Internetverbindung.',
	'wikibase-error-save-timeout' => 'Wir haben technische Schwierigkeiten. Deine Änderungen konnten daher nicht gespeichert werden.',
	'wikibase-error-remove-timeout' => 'Wir haben technische Schwierigkeiten. Das Entfernen konnten daher nicht ausgeführt werden.',
	'wikibase-error-autocomplete-connection' => 'Die Wikipedia-API konnte nicht abgefragt werden. Bitte versuche es später noch einmal.',
	'wikibase-error-autocomplete-response' => 'Serverantwort: $1',
	'special-itembytitle' => 'Wert nach Name',
	'special-itembylabel' => 'Wert nach Bezeichnung',
	'special-createitem' => 'Ein neues Datenelement erstellen',
	'wikibase-api-aliases-invalid-list' => 'Du musst einen der Parameter zu „set“, „add“, bzw. „remove“ nutzen.',
	'wikibase-api-no-token' => 'Es wurden keine Token angegeben.',
	'wikibase-api-no-data' => 'Es wurden keine zu verarbeitenden Daten gefunden.',
	'wikibase-api-cant-edit' => 'Der angemeldete Benutzer ist nicht berechtigt Bearbeitungen durchzuführen.',
	'wikibase-api-no-permissions' => 'Der angemeldete Benutzer verfügt über keine ausreichenden Berechtigungen.',
	'wikibase-api-id-xor-wikititle' => 'Gib für eine entsprechende Seite entweder die Kennung des Datenelements „id“ oder Informationspaare mit Angaben zu „site“ und „title“ an.',
	'wikibase-api-no-such-item' => 'Es wurde kein vorhandenes Datenelement gefunden.',
	'wikibase-api-no-such-item-id' => 'Es wurde zu dieser Kennung kein vorhandenes Datenelement gefunden.',
	'wikibase-api-link-exists' => 'Ein Artikel auf dem angegebenen Wiki ist bereits verknüpft.',
	'wikibase-api-add-with-id' => 'Mit der Kennung eines vorhanden Datenelements kann nichts hinzugefügt werden.',
	'wikibase-api-add-exists' => 'Zu einem vorhandenen Datenelement kann nichts hinzugefügt werden.',
	'wikibase-api-update-without-id' => 'Die Aktualisierung ohne eine frühere Kennung ist nicht möglich.',
	'wikibase-api-no-such-item-link' => 'Es wurde zu dieser Verknüpfung kein vorhandenes Datenelement gefunden.',
	'wikibase-api-create-failed' => 'Der Versuch ein neues Datenelement zu erstellen ist fehlgeschlagen.',
	'wikibase-api-modify-failed' => 'Der Versuch ein Datenelement zu ändern ist fehlgeschlagen.',
	'wikibase-api-save-failed' => 'Der Versuch das Datenelement zu speichern ist fehlgeschlagen.',
	'wikibase-api-invalid-contentmodel' => 'Die Inhaltsstruktur der Seite auf der das Datenelement gespeichert ist, ist ungültig.',
	'wikibase-api-alias-incomplete' => 'Es wurde keine Definition des Alias zum Datenelement gefunden.',
	'wikibase-api-alias-not-found' => 'Es wurde kein früherer Alias im Datenelement gefunden.',
	'wikibase-api-alias-found' => 'Es wurde ein früherer Alias im Datenelement gefunden.',
	'wikibase-api-not-recognized' => 'Die Richtlinie wird nicht erkannt.',
	'wikibase-api-label-or-description' => 'Verwende entweder Bezeichnung und/oder Beschreibung, aber lasse dies nicht offen.',
	'wikibase-api-label-not-found' => 'Es wurde keine frühere Bezeichnung in dieser Sprache im Datenelement gefunden.',
	'wikibase-api-description-not-found' => 'Es wurde keine frühere Beschreibung in dieser Sprache im Datenelement gefunden.',
	'wikibase-api-wrong-class' => 'Der Inhalt auf der gefundenen Seite entspricht nicht dem richtigen Typ.',
	'content-model-1001' => 'Wikibase-Datenelement',
);

/** German (formal address) (‪Deutsch (Sie-Form)‬)
 * @author Kghbln
 */
$messages['de-formal'] = array(
	'wikibase-move-error' => 'Sie können keine Seiten aus dem Datennamensraum heraus- bzw. in ihn hineinverschieben.',
	'wikibase-label-input-help-message' => 'Geben Sie den Namen für diesen Datensatz in $1 an.',
	'wikibase-description-input-help-message' => 'Geben Sie eine kurze Beschreibung in $1 an.',
	'wikibase-sitelinks-add' => 'fügen Sie eine Verknüpfung zu einer {{SITENAME}}-Seite hinzu',
	'wikibase-sitelinks-input-help-message' => 'Legen Sie eine Verknüpfung zu einer {{SITENAME}}-Seite fest.',
	'wikibase-error-save-generic' => 'Beim Versuch Ihre Änderungen zu speichern, ist ein Fehler aufgetreten. Ihre Änderungen konnten daher nicht gespeichert werden.',
	'wikibase-error-save-connection' => 'Ihre Änderungen konnten nicht gespeichert werden. Bitte prüfen Sie Ihre Internetverbindung.',
	'wikibase-error-remove-connection' => 'Beim Versuch das Entfernen auszuführen, ist ein Fehler aufgetreten. Bitte prüfen Sie Ihre Internetverbindung.',
	'wikibase-error-save-timeout' => 'Wir haben technische Schwierigkeiten. Ihre Änderungen konnten daher nicht gespeichert werden.',
	'wikibase-error-autocomplete-connection' => 'Die Wikipedia-API konnte nicht abgefragt werden. Bitte versuchen Sie es später noch einmal.',
	'wikibase-api-aliases-invalid-list' => 'Sie müssen einen der Parameter zu „set“, „add“, bzw. „remove“ nutzen.',
	'wikibase-api-id-xor-wikititle' => 'Geben Sie für eine entsprechende Seite entweder die Kennung des Datenelements „id“ oder Informationspaare mit Angaben zu „site“ und „title“ an.',
	'wikibase-api-label-or-description' => 'Verwenden Sie entweder Bezeichnung und/oder Beschreibung, aber lassen Sie dies nicht offen.',
);

/** Lower Sorbian (Dolnoserbski)
 * @author Michawiki
 */
$messages['dsb'] = array(
	'wikibase-desc' => 'Repozitorium strukturěrowanych datow',
	'wikibase-edit' => 'wobźěłaś',
	'wikibase-save' => 'składowaś',
	'wikibase-cancel' => 'pśetergnuś',
	'wikibase-add' => 'pśidaś',
	'wikibase-label-edit-placeholder' => 'pomjenjenje zapódaś',
	'wikibase-description-edit-placeholder' => 'wopisanje zapódaś',
	'wikibase-sitelink-site-edit-placeholder' => 'sedło pódaś',
	'wikibase-sitelink-page-edit-placeholder' => 'bok pódaś',
	'wikibase-label-input-help-message' => 'Zapódaj mě toś teje datoweje sajźby w $1.',
	'wikibase-description-input-help-message' => 'Zapódaj krotke wopisanje w $1.',
	'wikibase-sitelinks' => 'Boki Wikipedije, kótarež su z toś tym elementom zwězane',
	'wikibase-sitelinks-add' => 'wótkaz bokoju Wikipedije pśidaś',
	'wikibase-sitelinks-empty' => 'Až doněnta žedne boki Wikipedije njejsu zwězane z toś tym elementom.',
	'wikibase-sitelinks-input-help-message' => 'Póstaj wótkaz k nastawkoju Wikipedije.',
	'wikibase-remove' => 'wótpóraś',
	'wikibase-propertyedittool-full' => 'Lisćina gódnotow jo dopołna.',
	'wikibase-sitelinksedittool-full' => 'Wótkaze k bokam su južo za wšykne znate sedła nastajone.',
	'special-itembytitle' => 'Zapisk pó titelu',
	'special-itembylabel' => 'Zapisk pó pomjenjenju',
);

/** Spanish (Español)
 * @author Armando-Martin
 * @author Vivaelcelta
 */
$messages['es'] = array(
	'wikibase-desc' => 'Repositorio de datos estructurados',
	'wikibase-edit' => 'editar',
	'wikibase-save' => 'guardar',
	'wikibase-cancel' => 'cancelar',
	'wikibase-add' => 'añadir',
	'wikibase-save-inprogress' => 'Guardando...',
	'wikibase-remove-inprogress' => 'Eliminando...',
	'wikibase-label-edit-placeholder' => 'introducir la etiqueta',
	'wikibase-description-edit-placeholder' => 'introducir una descripción',
	'wikibase-move-error' => 'No puedes mover las páginas que se encuentran en el espacio de nombres de datos, y no puedes mover páginas hacia allí.',
	'wikibase-sitelink-site-edit-placeholder' => 'especificar el sitio',
	'wikibase-sitelink-page-edit-placeholder' => 'especificar la página',
	'wikibase-label-input-help-message' => 'Introducir el título de este conjunto de datos en  $1.',
	'wikibase-description-input-help-message' => 'Introducir una breve descripción en  $1.',
	'wikibase-sitelinks' => 'Páginas de {{SITENAME}} enlazadas a este elemento',
	'wikibase-sitelinks-add' => 'Agregar un enlace a una página de {{SITENAME}}',
	'wikibase-sitelinks-empty' => 'No hay todavía ninguna página de {{SITENAME}} enlazada a este elemento.',
	'wikibase-sitelinks-input-help-message' => 'Poner un enlace a un artículo de Wikipedia',
	'wikibase-remove' => 'eliminar',
	'wikibase-propertyedittool-full' => 'La lista de valores está completa.',
	'wikibase-propertyedittool-counter' => '$1 {{PLURAL:$1|entrada|entradas}}',
	'wikibase-propertyedittool-counter-pending' => '($2 $3 {{PLURAL:$1|entrada|entradas}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Un valor aún no guardado|$1 valores aún no guardados}}',
	'wikibase-sitelinksedittool-full' => 'Los enlaces a las páginas están ya definidos para todos los sitios conocidos.',
	'wikibase-disambiguation-title' => 'Desambiguación para "$1"',
	'wikibase-tooltip-error-details' => 'Detalles',
	'wikibase-error-save-generic' => 'Hubo un error al intentar guardar los cambios. No se pudieron almacenar tus cambios.',
	'wikibase-error-remove-generic' => 'Gubo un error al intentar realizar la eliminación.',
	'wikibase-error-save-connection' => 'No se pudieron almacenar tus cambios. Comprueba tu conexión a internet.',
	'wikibase-error-remove-connection' => 'Hubo un error al intentar realizar la eliminación. Comprueba tu conexión a internet.',
	'wikibase-error-save-timeout' => 'Estamos experimentando dificultades técnicas. No se pudieron almacenar tus cambios.',
	'wikibase-error-remove-timeout' => 'Estamos experimentando dificultades técnicas. No se pudo realizar la eliminación.',
	'wikibase-error-autocomplete-connection' => 'No se pudo consultar en la API de Wikipedia. Inténtalo de nuevo más tarde.',
	'wikibase-error-autocomplete-response' => 'Tu servidor respondió: $1',
	'special-itembytitle' => 'Artículo por título',
	'special-itembylabel' => 'Artículo por etiqueta',
	'special-createitem' => 'Crear un nuevo elemento',
	'wikibase-api-aliases-invalid-list' => 'Es necesario proporcionar el parámetro de configuración xor al agregar o quitar parámetros',
	'wikibase-api-no-token' => 'No se ha dado ninguna clave (token)',
	'wikibase-api-no-data' => 'No se ha encontrado ningún dato sobre el que operar',
	'wikibase-api-cant-edit' => 'El usuario que ha iniciado sesión no tiene permisos para editar',
	'wikibase-api-no-permissions' => 'El usuario que ha iniciado sesión no tiene derechos suficientes',
	'wikibase-api-id-xor-wikititle' => 'Proporciona el elemento ids o una pareja sitio-título para una página correspondiente',
	'wikibase-api-no-such-item' => 'No se pudo encontrar un elemento existente',
	'wikibase-api-no-such-item-id' => 'No se pudo encontrar un elemento existente para este identificador',
	'wikibase-api-link-exists' => 'Un artículo de la wiki especificada ya está vinculado',
	'wikibase-api-add-with-id' => 'No se puede agregar con el identificador de un elemento existente',
	'wikibase-api-add-exists' => 'No se puede agregar a un elemento existente',
	'wikibase-api-update-without-id' => 'No es posible la actualización sin un identificador previo',
	'wikibase-api-no-such-item-link' => 'No se pudo encontrar un elemento existente para este enlace',
	'wikibase-api-create-failed' => 'Error al intentar crear un nuevo elemento',
	'wikibase-api-modify-failed' => 'Error en el intento de modificación de un elemento',
	'wikibase-api-save-failed' => 'Error en el intento de guardar el elemento',
	'wikibase-api-invalid-contentmodel' => 'No es válido el modelo de contenido de la página en la que se almacena el elemento',
	'wikibase-api-alias-incomplete' => 'No se puede encontrar una definición del alias para el elemento',
	'wikibase-api-alias-not-found' => 'No se puede encontrar ningún alias anterior en el elemento',
	'wikibase-api-alias-found' => 'Se ha encotrado un alias anterior en el elemento',
	'wikibase-api-not-recognized' => 'No se reconoce la directiva',
	'wikibase-api-label-or-description' => 'Utiliza la etiqueta, la descripción o ambas, pero no pueden faltar las dos',
	'wikibase-api-label-not-found' => 'No se puede encontrar una etiqueta anterior para este idioma en el elemento',
	'wikibase-api-description-not-found' => 'No se puede encontrar una descripción anterior para este idioma en el elemento',
	'wikibase-api-wrong-class' => 'El contenido de la página encontrada no es del tipo correcto.',
	'content-model-1001' => 'Elemento de Wikibase',
);

/** Persian (فارسی)
 * @author ZxxZxxZ
 */
$messages['fa'] = array(
	'wikibase-desc' => 'داده‌های ساخت‌یافتهٔ مخزن',
	'wikibase-edit' => 'ویرایش',
	'wikibase-save' => 'ذخیره',
	'wikibase-cancel' => 'انصراف',
	'wikibase-add' => 'افزودن',
	'wikibase-label-edit-placeholder' => 'واردکردن برچسب',
	'wikibase-description-edit-placeholder' => 'واردکردن توضیحات',
	'wikibase-sitelink-site-edit-placeholder' => 'مشخص‌کردن وب‌گاه',
	'wikibase-sitelink-page-edit-placeholder' => 'مشخص‌کردن صفحه',
	'wikibase-label-input-help-message' => 'واردکردن عنوان این مجموعه داده‌ها در $1.',
	'wikibase-description-input-help-message' => 'توضیحات کوتاهی در $1 وارد کنید.',
	'wikibase-sitelinks' => 'صفحه‌هایی از ویکی‌پدیا که به این آیتم پیوند دارند',
	'wikibase-sitelinks-add' => 'افزودن پیوند به یک صفحه از ویکی‌پدیا',
	'wikibase-sitelinks-empty' => 'هیچ صفحه‌ای از ویکی‌پدیا به این آیتم پیوند ندارد.',
	'wikibase-sitelinks-input-help-message' => 'تنظیم یک لینک به مقاله‌ای از ویکی‌پدیا.',
	'wikibase-remove' => 'حذف',
	'wikibase-propertyedittool-full' => 'فهرست مقادیر کامل است.',
	'wikibase-sitelinksedittool-full' => 'پیوندها به صفحه‌ها در حال حاضر برای همهٔ وب‌گاه‌های شناخته‌شده تنظیم شده‌اند.',
	'special-itembytitle' => 'آیتم بر اساس عنوان',
	'special-itembylabel' => 'آیتم بر اساس برچسب',
);

/** French (Français)
 * @author Gomoko
 * @author Wyz
 */
$messages['fr'] = array(
	'wikibase-desc' => 'Référentiel de données structurées',
	'wikibase-edit' => 'modifier',
	'wikibase-save' => 'enregistrer',
	'wikibase-cancel' => 'annuler',
	'wikibase-add' => 'ajouter',
	'wikibase-label-edit-placeholder' => 'saisir étiquette',
	'wikibase-description-edit-placeholder' => 'saisir description',
	'wikibase-sitelink-site-edit-placeholder' => 'spécifier le site',
	'wikibase-sitelink-page-edit-placeholder' => 'spécifier la page',
	'wikibase-label-input-help-message' => 'Saisissez le titre de ces données définies dans $1.',
	'wikibase-description-input-help-message' => 'Saisissez une courte description dans $1.',
	'wikibase-sitelinks' => 'Pages Wikipédia liées à cet élément',
	'wikibase-sitelinks-add' => 'ajouter un lien vers une page de Wikipédia',
	'wikibase-sitelinks-empty' => "Aucune page de Wikipédia n'est encore liée à cet élément.",
	'wikibase-sitelinks-input-help-message' => 'Mettre un lien vers un article de Wikipédia.',
	'wikibase-remove' => 'retirer',
	'wikibase-propertyedittool-full' => 'La liste des valeurs est complète.',
	'wikibase-sitelinksedittool-full' => 'Les liens vers les pages sont déjà définis pour tous les sites connus.',
	'special-itembytitle' => 'Article par titre',
	'special-itembylabel' => 'Article par étiquette',
);

/** Galician (Galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'wikibase-desc' => 'Repositorio de datos estruturados',
	'wikibase-edit' => 'editar',
	'wikibase-save' => 'gardar',
	'wikibase-cancel' => 'cancelar',
	'wikibase-add' => 'engadir',
	'wikibase-save-inprogress' => 'Gardando...',
	'wikibase-remove-inprogress' => 'Eliminando...',
	'wikibase-label-edit-placeholder' => 'escriba unha etiqueta',
	'wikibase-description-edit-placeholder' => 'escriba unha descrición',
	'wikibase-move-error' => 'Non pode trasladar as páxinas que se atopan no espazo de nomes de datos, e tampouco pode mover páxinas a este espazo de nomes.',
	'wikibase-sitelink-site-edit-placeholder' => 'especifique o sitio',
	'wikibase-sitelink-page-edit-placeholder' => 'especifique a páxina',
	'wikibase-label-input-help-message' => 'Introduza o título deste conxunto de datos en $1.',
	'wikibase-description-input-help-message' => 'Introduza unha breve descrición en $1.',
	'wikibase-sitelinks' => 'Páxinas da Wikipedia con ligazóns cara a este elemento',
	'wikibase-sitelinks-add' => 'engada unha ligazón cara a unha páxina da Wikipedia',
	'wikibase-sitelinks-empty' => 'Ningunha páxina da Wikipedia ten ligazóns cara a este elemento.',
	'wikibase-sitelinks-input-help-message' => 'Defina unha ligazón cara a un artigo da Wikipedia.',
	'wikibase-remove' => 'eliminar',
	'wikibase-propertyedittool-full' => 'A lista de valores está completa.',
	'wikibase-propertyedittool-counter' => '($1 {{PLURAL:$1|entrada|entradas}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|entrada|entradas}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Un valor|$1 valores}} sen gardar',
	'wikibase-sitelinksedittool-full' => 'As ligazóns cara ás páxinas xa están definidas para todos os sitios coñecidos.',
	'wikibase-disambiguation-title' => 'Homónimos de "$1"',
	'wikibase-tooltip-error-details' => 'Detalles',
	'wikibase-error-save-generic' => 'Houbo un erro ao intentar gardar os cambios. Non se puideron almacenar os seus cambios.',
	'wikibase-error-remove-generic' => 'Houbo un erro ao levar a cabo a eliminación.',
	'wikibase-error-save-connection' => 'Non se puideron almacenar os seus cambios. Comprobe a súa conexión á internet.',
	'wikibase-error-remove-connection' => 'Houbo un erro ao levar a cabo a eliminación. Comprobe a súa conexión á internet.',
	'wikibase-error-save-timeout' => 'Estamos experimentando dificultades técnicas. Non se puideron almacenar os seus cambios.',
	'wikibase-error-remove-timeout' => 'Estamos experimentando dificultades técnicas. Non se puido levar a cabo a eliminación.',
	'wikibase-error-autocomplete-connection' => 'Non se puido pescudar na API da Wikipedia. Inténteo de novo máis tarde.',
	'wikibase-error-autocomplete-response' => 'O servidor respondeu: $1',
	'special-itembytitle' => 'Artigo por título',
	'special-itembylabel' => 'Artigo por etiqueta',
	'special-createitem' => 'Crear un novo elemento',
	'wikibase-api-aliases-invalid-list' => 'Utilice unicamente un dos parámetros "definir", "engadir" e "eliminar".',
	'wikibase-api-no-token' => 'Non se achegou ningún pase.',
	'wikibase-api-no-data' => 'Non se atopou ningún dato sobre o que operar.',
	'wikibase-api-cant-edit' => 'O usuario rexistrado non ten permitida a edición.',
	'wikibase-api-no-permissions' => 'O usuario rexistrado non ten os dereitos necesarios.',
	'wikibase-api-id-xor-wikititle' => 'Proporcione ou ben o elemento "id" ou parellas de "sitio" e "título" para a páxina que corresponda.',
	'wikibase-api-no-such-item' => 'Non se puido atopar un elemento existente.',
	'wikibase-api-no-such-item-id' => 'Non se puido atopar un elemento existente para ese identificador.',
	'wikibase-api-link-exists' => 'Xa está ligada unha páxina no wiki especificado.',
	'wikibase-api-add-with-id' => 'Non se pode engadir co identificador dun elemento existente.',
	'wikibase-api-add-exists' => 'Non se pode engadir a un elemento existente.',
	'wikibase-api-update-without-id' => 'A actualización non é posible sen un identificador previo.',
	'wikibase-api-no-such-item-link' => 'Non se puido atopar un elemento existente para esa ligazón.',
	'wikibase-api-create-failed' => 'Fallou o intento de crear un novo elemento.',
	'wikibase-api-modify-failed' => 'Fallou o intento de modificar o elemento.',
	'wikibase-api-save-failed' => 'Fallou o intento de gardar o elemento.',
	'wikibase-api-invalid-contentmodel' => 'O modelo de contido da páxina non é válido.',
	'wikibase-api-alias-incomplete' => 'Non se pode atopar unha definición do pseudónimo do elemento.',
	'wikibase-api-alias-not-found' => 'Non se pode atopar ningún pseudónimo anterior no elemento.',
	'wikibase-api-alias-found' => 'Atopouse un pseudónimo anterior no elemento.',
	'wikibase-api-not-recognized' => 'Non se recoñece a directiva.',
	'wikibase-api-label-or-description' => 'Utilice "etiqueta", "descrición" ou ambas.',
	'wikibase-api-label-not-found' => 'Non se pode atopar unha etiqueta anterior para esta lingua no elemento.',
	'wikibase-api-description-not-found' => 'Non se pode atopar unha descrición anterior para esta lingua no elemento.',
	'wikibase-api-wrong-class' => 'O contido da páxina atopada non é do tipo correcto.',
	'content-model-1001' => 'Elemento de Wikibase',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'wikibase-desc' => 'Repositorium vu strukturierte Date',
	'wikibase-edit' => 'bearbeite',
	'wikibase-save' => 'spyychere',
	'wikibase-cancel' => 'abbräche',
	'wikibase-add' => 'zuefiege',
	'wikibase-label-edit-placeholder' => 'Bezeichnig yygee',
	'wikibase-description-edit-placeholder' => 'Bschryybig yygee',
	'wikibase-label-input-help-message' => 'Gib dr Name fir dää Datesatz in $1 aa.',
	'wikibase-description-input-help-message' => 'Gib e churzi Bschryybig in $1 aa.',
	'wikibase-sitelinks' => '{{SITENAME}}-Syte, wu mit däm Datenelemänt verchnipft sin',
	'wikibase-sitelinks-add' => 'fieg e Verchnipfig zuen ere {{SITENAME}}-Syte zue',
	'wikibase-sitelinks-empty' => 'Bishär sin kei {{SITENAME}}-Syte mit däm Datenelemänt verchnipft.',
	'wikibase-remove' => 'uuseneh',
);

/** Hebrew (עברית)
 * @author Amire80
 */
$messages['he'] = array(
	'wikibase-desc' => 'מאגר נתונים מובנה',
	'wikibase-edit' => 'עריכה',
	'wikibase-save' => 'שמירה',
	'wikibase-cancel' => 'ביטול',
	'wikibase-add' => 'הוספה',
	'wikibase-save-inprogress' => 'מתבצעת שמירה...',
	'wikibase-remove-inprogress' => 'מתבצעת הסרה...',
	'wikibase-label-edit-placeholder' => 'הזינו תווית',
	'wikibase-description-edit-placeholder' => 'הזינו תיאור',
	'wikibase-move-error' => 'לא ניתן להעביר דפים במרחב השם נתונים, ולא ניתן להעביר דפים אליו.',
);

/** Upper Sorbian (Hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'wikibase-desc' => 'Repozitorij strukturowanych datow',
	'wikibase-edit' => 'wobdźěłać',
	'wikibase-save' => 'składować',
	'wikibase-cancel' => 'přetorhnyć',
	'wikibase-add' => 'přidać',
	'wikibase-save-inprogress' => 'Składuje so...',
	'wikibase-remove-inprogress' => 'Wotstronja so...',
	'wikibase-label-edit-placeholder' => 'pomjenowanje zapodać',
	'wikibase-description-edit-placeholder' => 'wopisanje zapodać',
	'wikibase-sitelink-site-edit-placeholder' => 'sydło podać',
	'wikibase-sitelink-page-edit-placeholder' => 'stronu podać',
	'wikibase-label-input-help-message' => 'Zapodaj mjeno tuteje datoweje sadźby w $1.',
	'wikibase-description-input-help-message' => 'Zapodaj krótke wopisanje w $1.',
	'wikibase-sitelinks' => 'Strony Wikipedije, kotrež su z tutym elementom zwjazane',
	'wikibase-sitelinks-add' => 'wotkaz stronje Wikipedije přidać',
	'wikibase-sitelinks-empty' => 'Dotal žane strony Wikipedije z tutym elementom zwjazane njejsu.',
	'wikibase-sitelinks-input-help-message' => 'Wotkaz k nastawkej Wikipedije stajić.',
	'wikibase-remove' => 'wotstronić',
	'wikibase-propertyedittool-full' => 'Lisćina hódnotow je dospołna.',
	'wikibase-propertyedittool-counter' => '$1 {{PLURAL:$1|zapisk|zapiskaj|zapiski|zapiskow}}',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|zapisk|zapiskaj|zapiski|zapiskow}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Jedna hódnota hišće njeskładowana|$1 hódnoće hišće njeskładowanej|$1 hódnoty hišće njeskładowane|$1 hódnotow hišće njeskładowanych}}',
	'wikibase-sitelinksedittool-full' => 'Wotkazy k stronam su hižo za wšě znate strony stajene.',
	'wikibase-disambiguation-title' => 'Strona wjacezmyslnosće za "$1"',
	'wikibase-tooltip-error-details' => 'Podrobnosće',
	'wikibase-error-save-connection' => 'Twoje změny njedadźa so składować. Prošu přepruwuj swój internetny zwisk.',
	'wikibase-error-autocomplete-response' => 'Serwer wotmołwi: $1',
	'special-itembytitle' => 'Zapisk po titulu',
	'special-itembylabel' => 'Zapisk po pomjenowanju',
	'special-createitem' => 'Nowy element wutworić',
	'wikibase-api-aliases-invalid-list' => 'Wužij jedyn z parametrow "set", "add" a "remove".',
	'wikibase-api-no-data' => 'Njejsu žane daty, kotrež dadźa so předźěłać.',
	'wikibase-api-cant-edit' => 'Přizjewjeny wužiwar njesmě wobdźěłać.',
	'wikibase-api-no-permissions' => 'Přizjewjeny wužiwar nima dosahace prawa.',
	'wikibase-api-no-such-item' => 'Njeje so eksistowacy element namakał.',
	'wikibase-api-no-such-item-id' => 'Njeje so žadyn eksistowacy element za tutón ID namakał.',
	'wikibase-api-link-exists' => 'Nastawk na podatym wikiju je hižo wotkazany.',
	'wikibase-api-add-exists' => 'Eksistowacemu elementej njeda so ničo přidać.',
	'wikibase-api-update-without-id' => 'Aktualizacija bjez prjedawšeho ID móžno njeje.',
	'wikibase-api-no-such-item-link' => 'Njeje so žadyn eksistowacy element za tutón wotkaz namakał.',
	'wikibase-api-invalid-contentmodel' => 'Wobsahowy model za stronu je njepłaćiwy.',
	'wikibase-api-alias-found' => 'Prjedawši alias je so za element namakał.',
	'wikibase-api-not-recognized' => 'Směrnica so njespóznawa.',
	'wikibase-api-label-or-description' => 'Wužij pak "pomjenowanje" pak "wopisanje".',
	'wikibase-api-label-not-found' => 'Njeje so žane prjedawše pomjenowanje za tutu rěč w elemenće namakało.',
	'wikibase-api-description-not-found' => 'Njeje so žane prjedawše wopisanje za tutu rěč w elemenće namakało.',
	'wikibase-api-wrong-class' => 'Wobsah na namakanej stronje korektny typ nima.',
	'content-model-1001' => 'Datowy element Wikibase',
);

/** Interlingua (Interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'wikibase-desc' => 'Repositorio de datos structurate',
	'wikibase-edit' => 'modificar',
	'wikibase-save' => 'salveguardar',
	'wikibase-cancel' => 'cancellar',
	'wikibase-add' => 'adder',
	'wikibase-save-inprogress' => 'Salveguarda…',
	'wikibase-remove-inprogress' => 'Remove…',
	'wikibase-label-edit-placeholder' => 'scribe etiquetta',
	'wikibase-description-edit-placeholder' => 'scribe description',
	'wikibase-move-error' => 'Tu non pote renominar paginas que es in le spatio de nomines de datos, e tu non pote displaciar paginas a in illo.',
	'wikibase-sitelink-site-edit-placeholder' => 'specifica sito',
	'wikibase-sitelink-page-edit-placeholder' => 'specifica pagina',
	'wikibase-label-input-help-message' => 'Entra le titulo de iste insimul de datos in $1.',
	'wikibase-description-input-help-message' => 'Entra un curte description in $1.',
	'wikibase-sitelinks' => 'Lista de paginas ligate a iste objecto',
	'wikibase-remove' => 'remover',
	'wikibase-propertyedittool-full' => 'Le lista de valores es complete.',
	'wikibase-propertyedittool-counter' => '$1 {{PLURAL:$1|entrata|entratas}}',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|entrata|entratas}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Un valor|$1 valores}} non ancora salveguardate',
	'wikibase-sitelinksedittool-full' => 'Ligamines a paginas es jam definite pro tote le sitos cognoscite.',
	'wikibase-disambiguation-title' => 'Disambiguation pro "$1"',
	'wikibase-tooltip-error-details' => 'Detalios',
	'wikibase-error-save-generic' => 'Un error occurreva durante le salveguarda. Le modificationes non poteva esser immagazinate.',
	'wikibase-error-remove-generic' => 'Un error occurreva durante le tentativa de remover.',
	'wikibase-error-save-connection' => 'Le modificationes non poteva esser immagazinate. Per favor verifica le connexion a internet.',
	'wikibase-error-remove-connection' => 'Un error occurreva durante le tentativa de remover. Verifica tu connexion a internet.',
	'wikibase-error-save-timeout' => 'Nos incontra difficultates technic. Le modificationes non poteva esser immagazinate.',
	'wikibase-error-remove-timeout' => 'Nos incontra difficultates technic. Non poteva exequer le remotion.',
	'wikibase-error-autocomplete-connection' => 'Non poteva consultar le API de Wikipedia. Per favor reproba plus tarde.',
	'wikibase-error-autocomplete-response' => 'Le servitor respondeva: $1',
	'special-itembytitle' => 'Objecto per titulo',
	'special-itembylabel' => 'Objecto per etiquetta',
	'special-createitem' => 'Crear un nove objecto',
);

/** Icelandic (Íslenska)
 * @author Snævar
 */
$messages['is'] = array(
	'wikibase-desc' => 'Skipulagður gagnaþjónn',
	'wikibase-edit' => 'breyta',
	'wikibase-save' => 'vista',
	'wikibase-cancel' => 'hætta við',
	'wikibase-add' => 'bæta við',
	'wikibase-save-inprogress' => 'Vista...',
	'wikibase-remove-inprogress' => 'Fjarlægi...',
	'wikibase-label-edit-placeholder' => 'bæta við merki',
	'wikibase-description-edit-placeholder' => 'setja inn lýsingu',
	'wikibase-move-error' => 'Ekki er mögulegt að færa síður í data nafnrýminu, né færa síður þangað.',
	'wikibase-sitelink-site-edit-placeholder' => 'tilgreindu vefsvæði',
	'wikibase-sitelink-page-edit-placeholder' => 'tilgreindu síðu',
	'wikibase-label-input-help-message' => 'Sláðu inn titil á þessum gögnum á $1.',
	'wikibase-description-input-help-message' => 'Sláðu inn stutta lýsingu á $1.',
	'wikibase-sitelinks' => 'Wikipedia síður sem tengja á þennan hlut',
	'wikibase-sitelinks-add' => 'bæta við tengil á Wikipedia síðu',
	'wikibase-sitelinks-empty' => 'Engar Wikipedia síður tengja á þennan hlut ennþá.',
	'wikibase-sitelinks-input-help-message' => 'Settu tengil á Wikipedia grein.',
	'wikibase-remove' => 'fjarlægja',
	'wikibase-propertyedittool-full' => 'Listi yfir gildi er tilbúinn.',
	'wikibase-propertyedittool-counter' => '($1 {{PLURAL:$1|færsla|færslur}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|færsla|færslur}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Eitt gildi er|$1 gildi eru}} ekki {{PLURAL:$1|vistað|vistuð}}',
	'wikibase-sitelinksedittool-full' => 'Tenglar á síður eru þegar virkir fyrir öll þekkt vefsvæði.',
	'wikibase-disambiguation-title' => 'Aðgreining fyrir "$1"',
	'wikibase-tooltip-error-details' => 'Nánar',
	'wikibase-error-save-generic' => 'Villa átti sér stað þegar þú reyndir að vista breytingarnar þínar. Vistun breytingarinnar þinnar mistókst.',
	'wikibase-error-remove-generic' => 'Villa átti sér stað þegar þú reyndir að fjarlægja hlut.',
	'wikibase-error-save-connection' => 'Mistókst að vista breytingarnar þínar. Athugaðu hvort þú sért tengd/ur netinu.',
	'wikibase-error-remove-connection' => 'Villa átti sér stað þegar þú reyndir að fjarlægja hlut. Vinsamlegast athugaðu hvort þú sért tengd/ur netinu.',
	'wikibase-error-save-timeout' => 'Við höfum orðið fyrir tæknilegum örðugleikum. Vistun breytingarinnar þinnar mistókst.',
	'wikibase-error-remove-timeout' => 'Við höfum orðið fyrir tæknilegum örðugleikum. Fjarlægingin mistókst.',
	'wikibase-error-autocomplete-connection' => 'Mistókst að senda fyrirspurn til Wikipedia. Vinsamlegast reyndu aftur síðar.',
	'wikibase-error-autocomplete-response' => 'Vefþjónninn svaraði: $1',
	'special-itembytitle' => 'Hlutur eftir titli',
	'special-itembylabel' => 'Hlutur eftir merki',
	'special-createitem' => 'Búa til nýjan hlut',
	'wikibase-api-aliases-invalid-list' => 'Notaðu einn af þessum stikum „set", „add" eða „remove".',
	'wikibase-api-no-token' => 'Enginn tóki tilgreindur.',
	'wikibase-api-no-data' => 'Engin gögn fundust til að byggja á.',
	'wikibase-api-cant-edit' => 'Innskráði notandinn hefur ekki réttindi til breytinga.',
	'wikibase-api-no-permissions' => 'Innskráði notandin hefur ekki tilskilin réttindi.',
	'wikibase-api-id-xor-wikititle' => 'Tilgreindu annaðhvort auðkenni eða bæði vefsvæði og titil viðkomandi síðu.',
	'wikibase-api-no-such-item' => 'Enginn tiltækur hlutur fannst.',
	'wikibase-api-no-such-item-id' => 'Mistókst að finna tiltækan hlut fyrir þetta auðkenni.',
	'wikibase-api-link-exists' => 'Grein á tilgreindum wiki er þegar tengd.',
	'wikibase-api-add-with-id' => 'Mistókst að bæta við með tiltæku auðkenni.',
	'wikibase-api-add-exists' => 'Mistókst að bæta við tiltækan hlut.',
	'wikibase-api-update-without-id' => 'Ómögulegt að uppfæra án fyrra auðkennis.',
	'wikibase-api-no-such-item-link' => 'Enginn tiltækur hlutur fannst fyrir þennan tengil.',
	'wikibase-api-create-failed' => 'Mistókst að búa til nýjan hlut.',
	'wikibase-api-modify-failed' => 'Mistókst að breyta hlut.',
	'wikibase-api-save-failed' => 'Mistókst að vista hlut.',
	'wikibase-api-label-or-description' => '"Merki" eða "lýsing" er ekki tilgreind. Tilgreindu annaðhvort eða bæði.',
	'wikibase-api-label-not-found' => 'Mistókst að finna fyrra merki fyrir þetta tungumál í hlutnum.',
	'wikibase-api-description-not-found' => 'Mistókst að finna fyrri lýsingu fyrir þetta tungumál í hlutnum.',
	'wikibase-api-wrong-class' => 'Innihald síðunnar sem fannst er ekki af réttri gerð.',
	'content-model-1001' => 'Wikibase hlutur',
);

/** Italian (Italiano)
 * @author Beta16
 */
$messages['it'] = array(
	'wikibase-desc' => 'Repository di dati strutturati',
	'wikibase-edit' => 'modifica',
	'wikibase-save' => 'salva',
	'wikibase-cancel' => 'annulla',
	'wikibase-add' => 'aggiungi',
	'wikibase-save-inprogress' => 'Sto salvando...',
	'wikibase-remove-inprogress' => 'Sto rimuovendo...',
	'wikibase-label-edit-placeholder' => 'inserisci etichetta',
	'wikibase-description-edit-placeholder' => 'inserisci descrizione',
	'wikibase-move-error' => 'Non puoi spostare le pagine che sono nel namespace "Data", e non puoi spostarci le pagine in esso.',
	'wikibase-sitelink-site-edit-placeholder' => 'specifica sito',
	'wikibase-sitelink-page-edit-placeholder' => 'specifica pagina',
	'wikibase-label-input-help-message' => 'Inserisci il titolo di questo insieme di dati in $1.',
	'wikibase-description-input-help-message' => 'Inserisci una breve descrizione in $1.',
	'wikibase-sitelinks' => 'Pagine di Wikipedia che sono collegate a questo elemento',
	'wikibase-sitelinks-add' => 'aggiungi un collegamento ad una pagina di Wikipedia',
	'wikibase-sitelinks-empty' => 'Nessuna pagina di Wikipedia ancora è collegata a questo elemento.',
	'wikibase-sitelinks-input-help-message' => 'Imposta un collegamento ad una voce di Wikipedia.',
	'wikibase-remove' => 'rimuovi',
	'wikibase-propertyedittool-full' => "L'elenco dei valori è completo.",
	'wikibase-propertyedittool-counter' => '($1 {{PLURAL:$1|elemento|elementi}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|elemento|elementi}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Un valore|$1 valori}} non ancora salvati',
	'wikibase-sitelinksedittool-full' => 'Sono già stati impostati collegamenti alle pagine per tutti i siti conosciuti.',
	'wikibase-disambiguation-title' => 'Disambigua per "$1"',
	'wikibase-tooltip-error-details' => 'Dettagli',
	'wikibase-error-save-generic' => 'Si è verificato un errore durante il salvataggio delle tue modifiche. Le modifiche apportate non possono essere memorizzate.',
	'wikibase-error-remove-generic' => 'Si è verificato un errore durante la rimozione.',
	'wikibase-error-save-connection' => 'Le modifiche apportate non possono essere memorizzate. Per favore, controlla la tua connessione ad internet.',
	'wikibase-error-remove-connection' => 'Si è verificato un errore durante la rimozione. Per favore, controlla la tua connessione ad internet.',
	'wikibase-error-save-timeout' => 'Stiamo riscontrando difficoltà tecniche. Le modifiche apportate non possono essere memorizzate.',
	'wikibase-error-remove-timeout' => 'Stiamo riscontrando difficoltà tecniche. Impossibile eseguire la rimozione.',
	'wikibase-error-autocomplete-connection' => 'Non è possibile interrogare le API di Wikipedia. Riprova più tardi.',
	'wikibase-error-autocomplete-response' => 'Risposta del server: $1',
	'special-itembytitle' => 'Elementi per titolo',
	'special-itembylabel' => 'Elementi per etichetta',
	'special-createitem' => 'Crea un nuovo elemento',
	'wikibase-api-aliases-invalid-list' => 'Utilizzare uno dei seguenti parametri: "set", "add" o "remove".',
	'wikibase-api-no-token' => 'Non è stato fornito alcun token.',
	'wikibase-api-no-data' => 'Non ci sono dati su cui operare.',
	'wikibase-api-cant-edit' => "All'utente connesso non è consentita la modifica.",
	'wikibase-api-no-permissions' => "L'utente connesso non ha diritti sufficienti.",
	'wikibase-api-id-xor-wikititle' => 'Fornire o l\'elemento "id" o la coppia "site" e "title" per una pagina corrispondente.',
	'wikibase-api-no-such-item' => 'Non è possibile trovare un elemento esistente.',
	'wikibase-api-no-such-item-id' => 'Non è possibile trovare un elemento esistente per questo ID.',
	'wikibase-api-link-exists' => 'Una voce sulla wiki indicata è già collegata.',
	'wikibase-api-add-with-id' => "Non è possibile aggiungere l'ID di un elemento esistente.",
	'wikibase-api-add-exists' => 'Non è possibile aggiungere ad un elemento esistente.',
	'wikibase-api-update-without-id' => 'Aggiornare senza un ID precedente non è possibile.',
	'wikibase-api-no-such-item-link' => 'Non è possibile trovare un elemento esistente per questo collegamento.',
	'wikibase-api-create-failed' => 'Fallito il tentativo di creazione di un nuovo elemento.',
	'wikibase-api-modify-failed' => "Fallito il tentativo di modifica dell'elemento.",
	'wikibase-api-save-failed' => "Fallito il tentativo di salvataggio dell'elemento.",
	'wikibase-api-invalid-contentmodel' => 'Il modello del contenuto per la pagina non è valido.',
	'wikibase-api-alias-incomplete' => "Non è possibile trovare una definizione dell'alias per l'elemento.",
	'wikibase-api-alias-not-found' => "Non è possibile trovare qualsiasi alias precedente nell'elemento.",
	'wikibase-api-alias-found' => "Trovato un alias precedente nell'elemento.",
	'wikibase-api-not-recognized' => 'La direttiva non è riconosciuta.',
	'wikibase-api-label-or-description' => 'Utilizzare almeno uno o entrambi tra "label" e "description".',
	'wikibase-api-label-not-found' => "Non è possibile trovare un'etichetta precedente per questa lingua nell'elemento.",
	'wikibase-api-description-not-found' => "Non è possibile trovare una descrizione precedente per questa lingua nell'elemento.",
	'wikibase-api-wrong-class' => 'Il contenuto della pagina trovata non è di un tipo corretto.',
	'content-model-1001' => 'Elemento wikibase',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'wikibase-desc' => '構造化されたデータリポジトリー',
	'wikibase-edit' => '編集',
	'wikibase-save' => '保存',
	'wikibase-cancel' => 'キャンセル',
	'wikibase-add' => '追加',
	'wikibase-label-edit-placeholder' => 'ラベルを入力',
	'wikibase-description-edit-placeholder' => '説明を入力',
	'wikibase-sitelink-site-edit-placeholder' => 'サイトを指定',
	'wikibase-sitelink-page-edit-placeholder' => 'ページを指定',
	'wikibase-sitelinks-add' => 'ウィキペディアのページへのリンクを追加',
	'wikibase-remove' => '除去',
);

/** Kurdish (Latin script) (‪Kurdî (latînî)‬)
 * @author George Animal
 */
$messages['ku-latn'] = array(
	'wikibase-edit' => 'biguherîne',
	'wikibase-save' => 'tomar bike',
	'wikibase-cancel' => 'betal bike',
	'wikibase-description-input-help-message' => 'Danasîneka kurt têkeve $1',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'wikibase-edit' => 'änneren',
	'wikibase-save' => 'späicheren',
	'wikibase-cancel' => 'ofbriechen',
	'wikibase-add' => 'derbäisetzen',
	'wikibase-description-edit-placeholder' => 'Beschreiwung aginn',
	'wikibase-sitelinks-add' => 'e Link op eng Wikipedia-Säit derbäisetzen',
	'wikibase-remove' => 'ewechhuelen',
);

/** Macedonian (Македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'wikibase-desc' => 'Складиште на структурирани податоци',
	'wikibase-edit' => 'уреди',
	'wikibase-save' => 'зачувај',
	'wikibase-cancel' => 'откажи',
	'wikibase-add' => 'додај',
	'wikibase-save-inprogress' => 'Зачувувам...',
	'wikibase-remove-inprogress' => 'Отстранувам...',
	'wikibase-label-edit-placeholder' => 'внесете натпис',
	'wikibase-description-edit-placeholder' => 'внесете опис',
	'wikibase-move-error' => 'Не можете да преместувате страници што се наоѓаат во податочниот именски простор. Исто така не можете да преместувате други страници во него.',
	'wikibase-sitelink-site-edit-placeholder' => 'укажете вики',
	'wikibase-sitelink-page-edit-placeholder' => 'укажете страница',
	'wikibase-label-input-help-message' => 'Внесете го насловот на податочниот збир во $1.',
	'wikibase-description-input-help-message' => 'Внесете краток опис за $1.',
	'wikibase-sitelinks' => 'Страници од {{SITENAME}} поврзани со оваа ставка',
	'wikibase-sitelinks-add' => 'додај врска до страница од {{SITENAME}}',
	'wikibase-sitelinks-empty' => '!Досега нема страници од {{SITENAME}} поврзани со оваа ставка.',
	'wikibase-sitelinks-input-help-message' => 'Задајте врска до статија од Википедија.',
	'wikibase-remove' => 'отстрани',
	'wikibase-propertyedittool-full' => 'Списокот на вредности е исполнет.',
	'wikibase-propertyedittool-counter' => '($1 {{PLURAL:$1|ставка|ставки}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|ставка|ставки}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Една вредност остана незачувана|$1 вредности останаа незачувани}}',
	'wikibase-sitelinksedittool-full' => 'Веќе се зададени врски за страници на сите познати викија.',
	'wikibase-disambiguation-title' => 'Појаснување за „$1“',
	'wikibase-tooltip-error-details' => 'Подробно',
	'wikibase-error-save-generic' => 'Се појави грешка при обидот да ги зачувам вашите промени, па затоа не успеав да ги складирам.',
	'wikibase-error-remove-generic' => 'Наидов на грешка при обидот да го извршам отстранувањето.',
	'wikibase-error-save-connection' => 'Не можев да ги зачувам промените. Проверете си ја врската со интернет.',
	'wikibase-error-remove-connection' => 'Наидов на грешка при обидот да го извршам отстранувањето. Проверете си ја врската со интернет.',
	'wikibase-error-save-timeout' => 'Се соочуваме со технички потешкотии. Не можам да ги зачувам вашите промени.',
	'wikibase-error-remove-timeout' => 'Се соочуваме со технички потешкотии. Не можев да го извршам отстранувањето.',
	'wikibase-error-autocomplete-connection' => 'Не можев да го добијам API-то на Википедија. Обидете се подоцна.',
	'wikibase-error-autocomplete-response' => 'Одговор на опслужувачот: $1',
	'special-itembytitle' => 'Ставка по наслов',
	'special-itembylabel' => 'Ставка по натпис',
	'special-createitem' => 'Создај нова ставка',
	'wikibase-api-aliases-invalid-list' => 'Треба да го укажете зададениот параметар xor или да додадете/отстраните параметри',
	'wikibase-api-no-token' => 'Нема зададено жетони',
	'wikibase-api-no-data' => 'Нема најдено податоци со кои би работел',
	'wikibase-api-cant-edit' => 'На најавениот корисник не му е дозволено да уредува',
	'wikibase-api-no-permissions' => 'Најавениот уредник нема доволно права',
	'wikibase-api-id-xor-wikititle' => 'Наведете назнаки на ставките или пар адреса-наслов за соодветна страница',
	'wikibase-api-no-such-item' => 'Не можев да пронајдам постоечката ставка',
	'wikibase-api-no-such-item-id' => 'Не пронајдов постоечка ставка за таа назнака',
	'wikibase-api-link-exists' => 'Веќе има врска со статијата на укажаното вики',
	'wikibase-api-add-with-id' => 'Не да додадам со назнаката на постечка ставка',
	'wikibase-api-add-exists' => 'Не можам да додадам во постоечка ставка',
	'wikibase-api-update-without-id' => 'Не можам да направам поднова без претходна назнака',
	'wikibase-api-no-such-item-link' => 'Не пронајдов постоечка ставка за таа врска',
	'wikibase-api-create-failed' => 'Обидот за создавање на нова ставка не успеа',
	'wikibase-api-modify-failed' => 'Обидот за измена на ставка не успеа',
	'wikibase-api-save-failed' => 'Обидот за зачувување на ставката не успеа',
	'wikibase-api-invalid-contentmodel' => 'Содржинскиот модел за страницата на која е зачувана ставката е неважечки',
	'wikibase-api-alias-incomplete' => 'Не можев да најдам дефиниција на алијасот на ставката',
	'wikibase-api-alias-not-found' => 'Не можев да најдам претходен алијас во ставката',
	'wikibase-api-alias-found' => 'Пронајдов претходен алијас во ставката',
	'wikibase-api-not-recognized' => 'Не ја препознав наредбата',
	'wikibase-api-label-or-description' => 'Користете натпис, опис или обете, но не ниедно од нив',
	'wikibase-api-label-not-found' => 'Не можев да пронајдам претходен натпис за овој јазик во ставката',
	'wikibase-api-description-not-found' => 'Не можев да пронајдам претходен опис за овој јазик во ставката',
	'wikibase-api-wrong-class' => 'Содржината на пронајдената страница не е од бараниот тип.',
	'content-model-1001' => 'Ставка во Викибазата',
);

/** Dutch (Nederlands)
 * @author McDutchie
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'wikibase-desc' => 'Repository voor gestructureerde gegevens',
	'wikibase-edit' => 'bewerken',
	'wikibase-save' => 'opslaan',
	'wikibase-cancel' => 'annuleren',
	'wikibase-add' => 'toevoegen',
	'wikibase-save-inprogress' => 'Bezig met opslaan...',
	'wikibase-remove-inprogress' => 'Bezig met verwijderen...',
	'wikibase-label-edit-placeholder' => 'geef een label op',
	'wikibase-description-edit-placeholder' => 'geef een beschrijving op',
	'wikibase-move-error' => "U kunt pagina's in de gegevensnaamruimte niet hernoemen en u kunt er geen pagina naartoe hernoemen.",
	'wikibase-sitelink-site-edit-placeholder' => 'site opgeven',
	'wikibase-sitelink-page-edit-placeholder' => 'pagina opgeven',
	'wikibase-label-input-help-message' => 'Geef de naam van deze gegevensset in in $1.',
	'wikibase-description-input-help-message' => 'Geef een korte beschrijving in in $1.',
	'wikibase-sitelinks' => "{{SITENAME}}-pagina's gekoppeld aan dit item",
	'wikibase-sitelinks-add' => 'verwijzing toevoegen naar een Wikipediapagina',
	'wikibase-sitelinks-empty' => "Er zijn nog geen {{SITENAME}}-pagina's gekoppeld aan dit item.",
	'wikibase-sitelinks-input-help-message' => 'Geef een koppeling in naar een pagina in Wikipedia.',
	'wikibase-remove' => 'verwijderen',
	'wikibase-propertyedittool-full' => 'De lijst met waarden is compleet.',
	'wikibase-propertyedittool-counter' => '($1 {{PLURAL:$1|ingang|ingangen}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|ingang|ingangen}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Eén waarde|$1 waarden}} nog niet opgeslagen',
	'wikibase-sitelinksedittool-full' => "Verwijzingen naar pagina's die al zijn ingesteld voor alle bekende sites.",
	'wikibase-disambiguation-title' => 'Disambiguatie voor "$1"',
	'wikibase-tooltip-error-details' => 'Details',
	'wikibase-error-save-generic' => 'Er is een fout opgetreden tijdens het opslaan van uw wijzigingen. Uw wijzigingen konden niet worden opgeslagen.',
	'wikibase-error-remove-generic' => 'Er is een fout opgetreden tijdens het verwijderen.',
	'wikibase-error-save-connection' => 'Uw wijzigingen konden niet worden opgeslagen. Controleer uw internetverbinding.',
	'wikibase-error-remove-connection' => 'Er is een fout opgetreden tijdens het verwijderen. Controleer uw internetverbinding.',
	'wikibase-error-save-timeout' => 'Wij ondervinden technische problemen. Uw wijzigingen kunnen niet worden opgeslagen.',
	'wikibase-error-remove-timeout' => 'Wij ondervinden technische problemen. Het item kan niet verwijderd worden.',
	'wikibase-error-autocomplete-connection' => 'Het was niet mogelijk de Wikipedia-API te bereiken. Probeer het later opnieuw.',
	'wikibase-error-autocomplete-response' => 'Antwoord van server: $1',
	'special-itembytitle' => 'Item gesorteerd op naam',
	'special-itembylabel' => 'Item gesorteerd op label',
	'special-createitem' => 'Nieuw item aanmaken',
	'wikibase-api-aliases-invalid-list' => 'Gebruik een van de parameters "set", "add" of "remove".',
	'wikibase-api-no-token' => 'Er is geen token verstrekt.',
	'wikibase-api-no-data' => 'Er zijn geen gegevens om te verwerken.',
	'wikibase-api-cant-edit' => 'De aangemelde gebruiker mag niet bewerken.',
	'wikibase-api-no-permissions' => 'De aangemelde gebruiker beschikt niet over voldoende rechten.',
	'wikibase-api-id-xor-wikititle' => 'Geef een "id" op voor een item, of paren van "site" en "title" voor een overeenkomstige pagina.',
	'wikibase-api-no-such-item' => 'Er kon geen bestaand item gevonden worden.',
	'wikibase-api-no-such-item-id' => 'Er kon geen bestaand item gevonden worden voor dit ID.',
	'wikibase-api-link-exists' => 'Er is al een pagina gekoppeld op de aangegeven wiki.',
	'wikibase-api-add-with-id' => 'Het is niet mogelijk toe te voegen met het ID van een bestaand item.',
	'wikibase-api-add-exists' => 'Het is niet mogelijk toe te voegen aan een bestaand item.',
	'wikibase-api-update-without-id' => 'Bijwerken zonder een eerder ID is niet mogelijk.',
	'wikibase-api-no-such-item-link' => 'Er kon geen bestaand item gevonden worden voor deze verwijzing.',
	'wikibase-api-create-failed' => 'Het aanmaken van een nieuw item is mislukt.',
	'wikibase-api-modify-failed' => 'Het wijzigen van het item is mislukt.',
	'wikibase-api-save-failed' => 'Het opslaan van het item is mislukt.',
	'wikibase-api-invalid-contentmodel' => 'Het contentmodel voor de pagina is ongeldig.',
	'wikibase-api-alias-incomplete' => 'Er kon geen definitie voor de alias voor het item gevonden worden.',
	'wikibase-api-alias-not-found' => 'Er kon geen eerdere alias in het item gevonden worden.',
	'wikibase-api-alias-found' => 'Er is een eerdere alias in het item aangetroffen.',
	'wikibase-api-not-recognized' => 'Het directief is niet herkend.',
	'wikibase-api-label-or-description' => 'Gebruik "label", "description" of beide.',
	'wikibase-api-label-not-found' => 'Er is geen eerder label in deze taal gevonden in het item.',
	'wikibase-api-description-not-found' => 'Er is geen eerdere beschrijving in deze taal gevonden in dit item.',
	'wikibase-api-wrong-class' => 'De inhoud van de gevonden pagina is niet van het juiste type.',
	'content-model-1001' => 'Wikibaseitem',
);

/** Norwegian Nynorsk (‪Norsk (nynorsk)‬)
 * @author Jeblad
 */
$messages['nn'] = array(
	'wikibase-desc' => 'Strukturert datalager',
	'wikibase-edit' => 'endre',
	'wikibase-save' => 'lagre',
	'wikibase-cancel' => 'avbryt',
	'wikibase-add' => 'legg til',
	'wikibase-label-edit-placeholder' => 'lag merkelapp',
	'wikibase-description-edit-placeholder' => 'lag beskriving',
	'wikibase-sitelink-site-edit-placeholder' => 'oppgje nettstad',
	'wikibase-sitelink-page-edit-placeholder' => 'oppgje side',
	'wikibase-label-input-help-message' => 'Lag ein merkelapp for datasettet knytt til $1.',
	'wikibase-description-input-help-message' => 'Lag ein kort beskriving for datasettet knytt til $1.',
	'wikibase-sitelinks' => 'Sidene som er knytt til dette datasettet',
	'wikibase-sitelinks-add' => 'Legg til ein nettstadlekk',
	'wikibase-sitelinks-empty' => 'Det fins ingen nettstadlekker',
	'wikibase-sitelinks-input-help-message' => 'Definer ein nettstadlekk slik at den peiker på ein artikkel.',
	'wikibase-remove' => 'fjern',
	'wikibase-propertyedittool-full' => 'Lista av verdiar er nå komplett',
	'wikibase-sitelinksedittool-full' => 'Det er ikkje fleire nettstadar tilgjengeleg',
	'special-itembytitle' => 'Eit datasett er påvist ved bruk av tittel',
	'special-itembylabel' => 'Eit datasett er påvist ved bruk av merkelapp',
);

/** Portuguese (Português)
 * @author Malafaya
 */
$messages['pt'] = array(
	'wikibase-desc' => 'Repositório de dados estruturados',
	'wikibase-edit' => 'editar',
	'wikibase-save' => 'gravar',
	'wikibase-cancel' => 'cancelar',
	'wikibase-add' => 'adicionar',
	'wikibase-label-edit-placeholder' => 'introduza etiqueta',
	'wikibase-description-edit-placeholder' => 'introduza descrição',
	'wikibase-label-input-help-message' => 'Introduza o título deste conjunto de dados em  $1.',
	'wikibase-description-input-help-message' => 'Insira uma curta descrição em  $1 .',
	'wikibase-sitelinks' => 'Páginas da Wikipédia ligadas a este item',
	'wikibase-sitelinks-add' => 'adicionar uma ligação para uma página da Wikipédia',
	'wikibase-sitelinks-empty' => 'Nenhuma página da Wikipédia liga a este item ainda.',
	'wikibase-remove' => 'remover',
);

/** Brazilian Portuguese (Português do Brasil)
 * @author Jaideraf
 */
$messages['pt-br'] = array(
	'wikibase-desc' => 'Repositório de dados estruturados',
	'wikibase-edit' => 'editar',
	'wikibase-save' => 'salvar',
	'wikibase-cancel' => 'cancelar',
	'wikibase-add' => 'adicionar',
	'wikibase-label-edit-placeholder' => 'insira um rótulo',
	'wikibase-description-edit-placeholder' => 'insira uma descrição',
	'wikibase-label-input-help-message' => 'Insira o título deste conjunto de dados em $1.',
	'wikibase-description-input-help-message' => 'Insira uma curta descrição em $1 .',
	'wikibase-sitelinks' => 'Páginas da Wikipédia linkadas a este item',
	'wikibase-sitelinks-add' => 'adicione um link para uma página da Wikipédia',
	'wikibase-sitelinks-empty' => 'Ainda não há qualquer página da Wikipédia linkada a este item.',
	'wikibase-remove' => 'remover',
);

/** Russian (Русский)
 * @author Kaganer
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'wikibase-desc' => 'Хранилище структурированных данных',
	'wikibase-edit' => 'редактировать',
	'wikibase-save' => 'сохранить',
	'wikibase-cancel' => 'отменить',
	'wikibase-add' => 'добавить',
	'wikibase-label-edit-placeholder' => 'введите метку',
	'wikibase-description-edit-placeholder' => 'введите описание',
	'wikibase-label-input-help-message' => 'Введите название этого набора данных в $1.',
	'wikibase-description-input-help-message' => 'Введите краткое описание в $1.',
	'wikibase-sitelinks' => 'Страницы Википедии, ссылающиеся на этот элемент',
	'wikibase-sitelinks-add' => 'добавить ссылку на страницу Википедии',
	'wikibase-sitelinks-empty' => 'Ни одна страница Википедии ещё не ссылается сюда.',
	'wikibase-remove' => 'убрать',
);

/** Swedish (Svenska)
 * @author Ainali
 * @author WikiPhoenix
 */
$messages['sv'] = array(
	'wikibase-desc' => 'Strukturerad datalagring',
	'wikibase-edit' => 'redigera',
	'wikibase-save' => 'spara',
	'wikibase-cancel' => 'avbryt',
	'wikibase-add' => 'lägg till',
	'wikibase-label-edit-placeholder' => 'ange etikett',
	'wikibase-description-edit-placeholder' => 'ange beskrivning',
	'wikibase-label-input-help-message' => 'Ange titeln på detta datat i  $1 .',
	'wikibase-description-input-help-message' => 'Ange en kort beskrivning i  $1.',
	'wikibase-sitelinks' => 'Wikipedia-sidor som är länkade till det här objektet',
	'wikibase-sitelinks-add' => 'lägg till en länk till en Wikipedia-sida',
	'wikibase-sitelinks-empty' => 'Inga Wikipedia-sidor länkade till det här objektet ännu.',
	'wikibase-sitelinks-input-help-message' => 'Ange en länk till en Wikipedia-artikel.',
	'wikibase-remove' => 'ta bort',
	'wikibase-propertyedittool-full' => 'Lista över värden är färdig.',
);

/** Tamil (தமிழ்)
 * @author Logicwiki
 */
$messages['ta'] = array(
	'wikibase-edit' => 'தொகு',
	'wikibase-save' => 'சேமி',
	'wikibase-cancel' => 'ரத்து செய்',
	'wikibase-add' => 'சேர்',
	'wikibase-remove' => 'நீக்கு',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'wikibase-edit' => 'సవరించు',
	'wikibase-save' => 'భద్రపరచు',
	'wikibase-cancel' => 'రద్దుచేయి',
	'wikibase-add' => 'చేర్చు',
	'wikibase-remove' => 'తొలగించు',
);

/** Simplified Chinese (‪中文(简体)‬)
 * @author Linforest
 */
$messages['zh-hans'] = array(
	'wikibase-desc' => '结构化数据存储库',
	'wikibase-edit' => '编辑',
	'wikibase-save' => '保存',
	'wikibase-cancel' => '取消',
	'wikibase-add' => '添加',
	'wikibase-label-edit-placeholder' => '输入标签',
	'wikibase-description-edit-placeholder' => '输入描述',
	'wikibase-sitelink-site-edit-placeholder' => '指定站点',
	'wikibase-sitelink-page-edit-placeholder' => '指定页面',
	'wikibase-label-input-help-message' => '采用$1输入该数据集的标题。',
	'wikibase-description-input-help-message' => '采用$1输入简要说明。',
	'wikibase-sitelinks' => '链接到此项的维基百科页面',
	'wikibase-sitelinks-add' => '添加指向特定维基百科页面的链接',
	'wikibase-sitelinks-empty' => '尚无维基百科页面链接到此项目。',
	'wikibase-sitelinks-input-help-message' => '设置一条指向特定维基百科文章的链接。',
	'wikibase-remove' => '删除',
	'wikibase-propertyedittool-full' => '取值列表已完整。',
	'wikibase-sitelinksedittool-full' => '已经为所有的已知站点设置了指向页面的链接。',
	'special-itembytitle' => '按标题排序的项目',
	'special-itembylabel' => '按标签排序的项目',
);

