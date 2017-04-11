<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class InesApi
 */
class InesApi extends CrmApi
{

	/**
	 * Force la suppression / mise à jour de l'ID de session du web-service
	 * Utilisé par le bouton "Tester la connexion" de l'onglet de config
	 *
	 * @return	int (id de session)
	 * @throws 	ApiErrorException
	 */
    public function refreshSessionID()
	{
		$this->integration->unsetWebServiceCurrentSessionID();
		$newSessionID = $this->getSessionID();
		return $newSessionID;
	}


	/**
	 * Retourne la liste des champs disponibles chez INES
	 *
	 * @return array
	 */
	public function getLeadFields()
	{
        // Lecture de la config définie chez INES
		$inesConfig = $this->getInesSyncConfig();

        // Clés qui seront mappées avec les tableaux de valeurs ci-dessous
		$fieldKeys = array(
            'concept', 'inesKey', 'inesLabel', 'isCustomField', 'isMappingRequired', 'autoMapping', 'excludeFromEcrasableConfig', 'atmtCustomFieldToCreate'
        );

        /////// ETAPE 1
		// Tous les champs INES standards mappables avec ATMT
        // Ne sont pas inclus : Score et Désabonnement car gérés hors-mapping
        // Attention : le champ e-mail ne doit pas être auto-mappé, sinon la synchro peut ne pas être déclenchée par CrmAbstractIntegration::pushLead
		$defaultInesFields = array();
		$defaultInesFields[] = array('contact', 'InternalContactRef', 'Référence INES (contact)', false, true, 'ines_contact_ref', true, ['alias' => "ines_contact_ref", 'type' => "number"]);
		$defaultInesFields[] = array('contact', 'InternalCompanyRef', 'Référence INES (société)', false, true, 'ines_client_ref', true, ['alias' => "ines_client_ref", 'type' => "number"]);
		$defaultInesFields[] = array('contact', 'PrimaryMailAddress', 'E-mail principal', false, true, false, true, false);
        $defaultInesFields[] = array('contact', 'Genre', "Civilité (contact)", false, false, false, false, ['alias' => "ines_contact_civilite"]);
        $defaultInesFields[] = array('contact', 'LastName', "Nom (contact)", false, false, 'lastname', false, false);
        $defaultInesFields[] = array('contact', 'FirstName', "Prénom (contact)", false, false, 'firstname', false, false);
        $defaultInesFields[] = array('contact', 'Function', "Fonction (contact)", false, false, false, false, ['alias' => "ines_contact_fonction"]);
        $defaultInesFields[] = array('contact', 'Type', 'Type (contact)', false, false, false, false, ['alias' => "ines_contact_type", 'type' => "select", 'valuesFromWS' => "GetTypeContactList", 'firstValueAsDefault' => true]);
        $defaultInesFields[] = array('contact', 'Service', 'Service (contact)', false, false, false, false, ['alias' => "ines_contact_service"]);
        $defaultInesFields[] = array('contact', 'BussinesTelephone', "Téléphone bureau (contact)", false, false, false, false, ['alias' => "ines_contact_tel_bureau", 'type' => "tel"]);
        $defaultInesFields[] = array('contact', 'HomeTelephone', "Téléphone domicile (contact)", false, false, false, false, ['alias' => "ines_contact_tel_domicile", 'type' => "tel"]);
        $defaultInesFields[] = array('contact', 'MobilePhone', "Téléphone mobile (contact)", false, false, false, false, ['alias' => "ines_contact_tel_mobile", 'type' => "tel"]);
        $defaultInesFields[] = array('contact', 'Fax', 'Fax (contact)', false, false, false, false, ['alias' => "ines_contact_fax", 'type' => "tel"]);
        $defaultInesFields[] = array('contact', 'HomeAddress', "Adresse 1 (contact)", false, false, false, false, ['alias' => "ines_contact_adr1"]);
        $defaultInesFields[] = array('contact', 'BusinessAddress', "Adresse 2 (contact)", false, false, false, false, ['alias' => "ines_contact_adr2"]);
        $defaultInesFields[] = array('contact', 'ZipCode', "Code postal (contact)", false, false, false, false, ['alias' => "ines_contact_cp"]);
        $defaultInesFields[] = array('contact', 'City', "Ville (contact)", false, false, false, false, ['alias' => "ines_contact_ville"]);
        $defaultInesFields[] = array('contact', 'State', "Région (contact)", false, false, false, false, ['alias' => "ines_contact_region"]);
        $defaultInesFields[] = array('contact', 'Country', "Pays (contact)", false, false, false, false, ['alias' => "ines_contact_pays"]);
        $defaultInesFields[] = array('contact', 'Language', "Langue (contact)", false, false, false, false, ['alias' => "ines_contact_lang"]);
        $defaultInesFields[] = array('contact', 'Author', "Responsable (contact)", false, false, false, false, ['alias' => "ines_contact_resp", 'type' => "select", 'valuesFromWS' => "GetUserInfoFromUserRef"]);
        $defaultInesFields[] = array('contact', 'Comment', "Remarque (contact)", false, false, false, false, ['alias' => "ines_contact_remarque"]);
        $defaultInesFields[] = array('contact', 'Confidentiality', 'Diffusion (contact)', false, false, false, false, ['alias' => "ines_contact_diffusion", 'type' => "select", 'values' => ["-1" => "lecture seule", "0" => "lecture / écriture", "1" => "confidentiel"] ]);
        $defaultInesFields[] = array('contact', 'DateOfBirth', "Date d'anniversaire (contact)", false, false, false, false, ['alias' => "ines_contact_birthday", 'type' => "date"]);
        $defaultInesFields[] = array('contact', 'Rang', 'Etat (contact)', false, false, false, false, ['alias' => "ines_contact_etat", 'type' => "select", 'values' => ["0" => "secondaire", "1" => "principal", "2" => "archivé"] ]);
        $defaultInesFields[] = array('contact', 'SecondaryMailAddress', 'Email 2 (contact)', false, false, false, false, ['alias' => "ines_contact_email2"]);
        $defaultInesFields[] = array('contact', 'NPai', 'NPAI (contact)', false, false, 'ines_contact_npai', true, ['alias' => 'ines_contact_npai', 'type' => "boolean"]);
        $defaultInesFields[] = array('client', 'CompanyName', 'Société', false, true, 'company', true, false);
        $defaultInesFields[] = array('client', 'Type', 'Type (société)', false, false, false, false, ['alias' => "ines_client_type", 'type' => "select", 'valuesFromWS' => "GetTypeClientList", 'firstValueAsDefault' => true]);
        $defaultInesFields[] = array('client', 'Manager', 'Resp. Dossier (société)', false, false, false, false, ['alias' => "ines_client_resp_dossier", 'type' => "select", 'valuesFromWS' => "GetUserInfoFromUserRef"]);
        $defaultInesFields[] = array('client', 'SalesResponsable', 'Commercial (société)', false, false, false, false, ['alias' => "ines_client_commercial", 'type' => "select", 'valuesFromWS' => "GetUserInfoFromRHRef"]);
        $defaultInesFields[] = array('client', 'TechnicalResponsable', 'Resp. technique (société)', false, false, false, false, ['alias' => "ines_client_resp_tech", 'type' => "select", 'valuesFromWS' => "GetUserInfoFromUserRef"]);
        $defaultInesFields[] = array('client', 'Phone', "Téléphone (société)", false, false, false, false, ['alias' => "ines_client_tel", 'type' => "tel"]);
        $defaultInesFields[] = array('client', 'Fax', 'Fax (société)', false, false, false, false, ['alias' => "ines_client_fax", 'type' => "tel"]);
        $defaultInesFields[] = array('client', 'Address1', "Adresse ligne 1 (société)", false, false, false, false, ['alias' => "ines_client_adr1"]);
        $defaultInesFields[] = array('client', 'Address2', "Adresse ligne 2 (société)", false, false, false, false, ['alias' => "ines_client_adr2"]);
        $defaultInesFields[] = array('client', 'ZipCode', "Code postal (société)", false, false, false, false, ['alias' => "ines_client_cp"]);
        $defaultInesFields[] = array('client', 'City', "Ville (société)", false, false, false, false, ['alias' => "ines_client_ville"]);
        $defaultInesFields[] = array('client', 'State', "Région (société)", false, false, false, false, ['alias' => "ines_client_region"]);
        $defaultInesFields[] = array('client', 'Country', "Pays (société)", false, false, false, false, ['alias' => "ines_client_pays"]);
        $defaultInesFields[] = array('client', 'Origin', 'Origine (société)', false, false, false, false, ['alias' => "ines_client_origine", 'type' => "select", 'valuesFromWS' => "GetOriginList"]);
        $defaultInesFields[] = array('client', 'Website', "Site internet (société)", false, false, false, false, ['alias' => "ines_client_site_web", 'type' => "url"]);
        $defaultInesFields[] = array('client', 'Confidentiality', 'Diffusion (société)', false, false, false, false, ['alias' => "ines_client_diffusion", 'type' => "select", 'values' => ["-1" => "lecture seule", "0" => "lecture / écriture", "1" => "confidentiel"]]);
        $defaultInesFields[] = array('client', 'Comments', 'Remarque (société)', false, false, false, false, ['alias' => "ines_client_remarque"]);
        $defaultInesFields[] = array('client', 'CustomerNumber', 'N° de client (société)', false, false, false, false, ['alias' => "ines_client_num_client", 'type' => "number"]);
        $defaultInesFields[] = array('client', 'Language', 'Langue (société)', false, false, false, false, ['alias' => "ines_client_lang"]);
        $defaultInesFields[] = array('client', 'Activity', 'Activité (société)', false, false, false, false, ['alias' => "ines_client_activite"]);
        $defaultInesFields[] = array('client', 'Scoring', 'Score (société)', false, false, false, false, ['alias' => "ines_client_score"]);

        $inesFields = array();
		foreach($defaultInesFields as $field) {
			$inesFields[] = array_combine($fieldKeys, $field);
		}


        /////// ETAPE 2
        //// Ajout des champs custom INES, pour les contacts INES et les sociétés INES

        $availableCustomFieldTypes = [
            'text' => 'text', 'url' => 'url', 'list' => 'select', 'user' => 'select', 'numeral' => 'number', 'boolean' => 'boolean', 'date' => 'date'
        ];

        $concepts = ['contact' => 'Contact', 'client' => 'Company'];
        foreach($concepts as $concept => $conceptLabel) {
            if (is_array($inesConfig[$conceptLabel.'CustomFields'])) {
                foreach($inesConfig[$conceptLabel.'CustomFields'] as $field) {

                    // Conversion du type de champ INES en type de champ ATMT
                    if ( !isset($availableCustomFieldTypes[$field['Type']])) {
                        continue;
                    }
                    $atmtType = $availableCustomFieldTypes[$field['Type']];

                    $atmtCustomFieldToCreate = [
                        'alias' => 'ines_'.$concept.'_custom_'.$field['InesID'],
                        'type' => $atmtType
                    ];

                    // Type INES "user"
                    if ($field['Type'] == 'user') {
                        $atmtCustomFieldToCreate['valuesFromWS'] = 'GetUserInfoFromUserRef';
                    }
                    // Liste de valeurs avec des clés identiques
                    else if ($atmtType == 'select') {

                        $values = end($field['ValueList']);

                        // Exclusion des valeurs vides
                        $values = array_filter($values, function ($value) {
                            return !empty($value);
                        });

                        // Pour ces listes de valeurs, on n'utilise que les clés : les étiquettes sont vierges
                        $atmtCustomFieldToCreate['values'] = array_combine(
                            array_values($values),
                            array_fill(0, count($values), "")
                        );
                    }

                    $inesLabel = $field['InesName'].' ('.(($concept == 'contact') ? 'contact' : 'société').')';

                    $inesFields[] = array_combine(
                        $fieldKeys,
                        [$concept, $field['InesID'], $inesLabel, true, false, false, false, $atmtCustomFieldToCreate]
                    );
                }
            }
        }


        /////// ETAPE 3
        // Remplissage des clés/valeurs possibles pour les champs dont les valeurs dépendent d'un WS INES

        foreach($inesFields as $k => $field) {

            // Si le champ est concerné...
            if ($field['atmtCustomFieldToCreate'] !== false && isset($field['atmtCustomFieldToCreate']['valuesFromWS'])) {

                // Le WS existe-t-il dans la config ?
                $wsName = $field['atmtCustomFieldToCreate']['valuesFromWS'];
                if (isset($inesConfig['ValuesFromWS'][$wsName])) {

                    // Si oui on utilise les clés/valeurs lues dans ce WS pour le champ courant
                    $values = $inesConfig['ValuesFromWS'][$wsName];
                    $inesFields[$k]['atmtCustomFieldToCreate']['values'] = $values;
                    unset($inesFields[$k]['atmtCustomFieldToCreate']['ValuesFromWS']);
                }
                else {
                    $this->integration->log("INEW WS not found : $wsName");
                }
            }
        }

		return $inesFields;
	}


	/**
	 * Appelé par le TRIGGER "push lead to integration", en sortie d'un FORM, d'une CAMPAIGN...
	 *
	 * @param 	array 							$mappedData		Ces données ne sont pas utilisées
	 * @param 	Mautic\LeadBundle\Entity\Lead	$lead
	 */
	public function createLead($mappedData, Lead $lead)
	{
		$leadId = $lead->getId();
		$company = $this->integration->getLeadMainCompany($leadId);

		// Un lead n'est synchronisé que s'il possède au minimum un email et une société
		if ( !empty($lead->getEmail()) && !empty($company)) {
			try {
				$this->syncLeadToInes($lead, true);

				// Si un lead est synchronisé par une action directe "push contact to integaration",
				// on le retire d'une éventuelle file d'attente, dédiée aux synchro asynchrones via un CRONJOB
				$this->integration->dequeuePendingLead($lead->getId());
			}
			catch (\Exception $e) {
				$this->integration->logIntegrationError($e);
			}
		}
	}


	/**
	 * Pousse n'importe quel lead, passé en paramètre, vers INES CRM
	 * Optimisé pour enchaîner les appels si nécessaire
	 *
	 * @param 	Mautic\LeadBundle\Entity\Lead	$lead
     * @param   bool    $addLeadWhenUpdating    Indique s'il faut créer chez INES un webLead dans le cas d'une mise à jour
	 *
	 * @return 	bool	Succès ou échec de l'opération
	 */
	public function syncLeadToInes(Lead $lead, $addLeadWhenUpdating = false)
	{
		$leadId = $lead->getId();
		$leadPoints = $lead->getPoints();
        $leadDesaboFlag = empty($lead->getDoNotContact()->toArray()) ? 0 : 1;
		$company = $this->integration->getLeadMainCompany($leadId, false);
        $dontSyncToInes = $this->integration->getDontSyncFlag($lead);

		if ( !isset($company['companyname']) || empty($company['companyname']) || $dontSyncToInes) {
			return false;
		}

		// Lecture de l'intégralité des champs du lead courant
		$rawFields = $lead->getFields();
		$fieldsValues = array();
		foreach($rawFields as $fieldGroup => $localFields) {
			foreach($localFields as $fieldKey => $field) {
				$fieldsValues[$fieldKey] = $field['value'];
			}
		}

		// Application du mapping au lead courant
		// En dissociant les informations du contact et de la société (= client)
		// ainsi que les champs standards et custom (chez INES)
		$mappedDatas = array(
			'contact' => array(
				'standardFields' => array(),
				'customFields' => array()
			),
			'client' => array(
				'standardFields' => array(),
				'customFields' => array()
			)
		);

		// Structure pour mémoriser les champs non-écrasables
		$inesProtectedFields = array(
			'contact' => array(),
			'client' => array()
		);

		$mapping = $this->integration->getMapping();

		foreach($mapping as $mappingItem) {

			// Valeur du lead pour le champ courant
			// Si non définie, on ne la mémorise pas dans les données mappées
            if ($mappingItem['atmtFieldKey'] == 'company') { // Cas particulier : société
                $leadValue = $company['companyname'];
            }
            else { // Cas général
				$leadValue = $fieldsValues[ $mappingItem['atmtFieldKey'] ];
				if ($leadValue == null) {
					continue;
				}
			}

			// Clé du champ chez INES
			$inesFieldKey = $mappingItem['inesFieldKey'];

			// Concept chez INES à qui est rattaché le champ : contact ou client
			$concept = $mappingItem['concept'];

			// Si le champ n'est pas écrasable chez INES, on le mémorise : sera utile lors de l'UPDATE (voir plus bas)
			if ( !$mappingItem['isEcrasable']) {
				array_push($inesProtectedFields[$concept], $inesFieldKey);
			}

			// Champ de base ou custom (chez INES) ?
			$fieldCategory = ($mappingItem['isCustomField'] == 0) ? 'standardFields' : 'customFields';

			// Mémorisation et classement de la valeur du champ
			$mappedDatas[$concept][$fieldCategory][$inesFieldKey] = $leadValue;
		}


		// Lecture des valeurs des clés INES pour le contact et la société
		// Si c'est un nouveau lead, elles sont inconnues, sinon elles doivent avoir été mémorisées précédemment
		$internalContactRef = isset($mappedDatas['contact']['standardFields']['InternalContactRef']) ? $mappedDatas['contact']['standardFields']['InternalContactRef'] : 0;
		$internalCompanyRef = isset($mappedDatas['contact']['standardFields']['InternalCompanyRef']) ? $mappedDatas['contact']['standardFields']['InternalCompanyRef'] : 0;

		// CREATE
		// Si l'une de ces deux clé est inconnue, on crée la société et le contact chez INES
		if ( !$internalCompanyRef || !$internalContactRef) {

			// On utilise un modèle par défaut pour construire les données à synchroniser
			$datas = $this->getClientWithContactsTemplate();

			// Puis on hydrate les champs standards liés au concept 'client'
			foreach($mappedDatas['client']['standardFields'] as $inesFieldKey => $fieldValue) {
				$datas['client'][$inesFieldKey] = $fieldValue;
			}

			// Puis les champs standards liés au concept 'contact'
			foreach($mappedDatas['contact']['standardFields'] as $inesFieldKey => $fieldValue) {
				$datas['client']['Contacts']['ContactInfoAuto'][0][$inesFieldKey] = $fieldValue;
			}

            // Si non renseigné, le champ "Type de société" est imposé par la config définie chez INES
			if ( !isset($datas['client']['Type']) || $datas['client']['Type'] == 0) {
                $inesConfig = $this->getInesSyncConfig();
                $datas['client']['Type'] = $inesConfig['SocieteType'];
            }

            // référence du contact ATMT
			$datas['client']['Contacts']['ContactInfoAuto'][0]['AutomationRef'] = $leadId;

            // scoring ATMT
            $datas['client']['Contacts']['ContactInfoAuto'][0]['Scoring'] = $leadPoints;

            // Flag désabonnement
            $datas['client']['Contacts']['ContactInfoAuto'][0]['Desabo'] = $leadDesaboFlag;

			// Requête SOAP : Création chez INES
			$response = $this->request('ws/wsAutomationsync.asmx', 'AddClientWithContacts', $datas, true, true);

			if ( !isset($response['AddClientWithContactsResult']['InternalRef']) ||
				 !isset($response['AddClientWithContactsResult']['Contacts']['ContactInfoAuto']['InternalRef'])
			) {
				return false;
			}

			// et récupération en retour d'une clé contact et client
			$internalCompanyRef = $response['AddClientWithContactsResult']['InternalRef'];
			$internalContactRef = $response['AddClientWithContactsResult']['Contacts']['ContactInfoAuto']['InternalRef'];
			if ( !$internalCompanyRef || !$internalContactRef) {
				return false;
			}

			// Mémorisation dans le lead ATMT des clés contact et client
			$this->integration->setInesKeysToLead($lead, $internalCompanyRef, $internalContactRef);

			// Si un canal de lead a été configuré chez INES, la création du contact doit être suivie par l'écriture d'un lead (au sens INES du terme)
			$inesConfig = $this->getInesSyncConfig();
			if (isset($inesConfig['LeadRef']) && $inesConfig['LeadRef'] >= 0) {
				$this->addLeadToInesContact(
					$internalContactRef,
					$internalCompanyRef,
					$datas['client']['Contacts']['ContactInfoAuto'][0]['PrimaryMailAddress'],
					$inesConfig['LeadRef']
				);
			}
		}

		// UPDATE
		// Si les deux clés sont déjà connues, on met à jour la société et le contact chez INES
		else {

			// Avant tout update, on récupère les données existantes chez INES
			$clientWithContact = array(
				'contact' => $this->getContactFromInes($internalContactRef),
				'client' => $this->getClientFromInes($internalCompanyRef)
			);

			// Si le contact ou le client n'existent plus chez INES, c'est qu'ils ont été supprimés
			// Il faut donc effacer en local les clés connues et recommencer la synchro de ce lead
			if ($clientWithContact['contact'] === false || $clientWithContact['client'] === false) {
				$lead = $this->integration->setInesKeysToLead($lead, 0, 0);
				return $this->syncLeadToInes($lead);
			}

			// Mise à jour du contact, si nécessaire, puis du client, si nécessaire
			foreach($clientWithContact as $concept => $conceptDatas) {

				$updateNeeded = false;
				foreach($mappedDatas[$concept]['standardFields'] as $inesFieldKey => $fieldValue) {

					if ( !isset($conceptDatas[$inesFieldKey])) {
						continue;
					}

					$currentFieldValue = $conceptDatas[$inesFieldKey];

					$isProtectedField = in_array($inesFieldKey, $inesProtectedFields[$concept]);

					// Un champ est mis à jour s'il a changé, et à condition qu'il ne soit pas non-écrasable, sauf s'il est vide chez INES
					if ($currentFieldValue != $fieldValue &&
						( !$isProtectedField || empty($currentFieldValue))
					){
						$conceptDatas[$inesFieldKey] = $fieldValue;
						$updateNeeded = true;
					}
				}

				// Appel du WS seulement si nécessaire
				if ($updateNeeded) {
					// Données à transmettre au web-service
					$wsDatas = array($concept => $conceptDatas);

					// Update du concept "client / société"
					if ($concept == 'client') {

						$wsDatas['client']['ModifiedDate'] = date("Y-m-d\TH:i:s");
						// Appel du WS officiel
						$response = $this->request('ws/wsicm.asmx', 'UpdateClient', $wsDatas, true, true);

						if ( !isset($response['UpdateClientResult']['InternalRef'])) {
							return false;
						}
					}
					// Update du concept "contact"
					else {
                        $wsDatas['contact']['ModificationDate'] = date("Y-m-d\TH:i:s");
						$wsDatas['contact']['AutomationRef'] = $leadId;
						$wsDatas['contact']['Scoring'] = $leadPoints;
                        $wsDatas['contact']['Desabo'] = $leadDesaboFlag;
						$wsDatas['contact']['IsNew'] = false;

						// Filtrage des champs : on ne conserve que ceux demandés par le WS spécifique ATMT
						$contactDatas = $this->getContactTemplate();
						foreach($contactDatas as $key => $value) {
							if (isset($wsDatas['contact'][$key])) {
								$contactDatas[$key] = $wsDatas['contact'][$key];
							}
						}
						$wsDatas['contact'] = $contactDatas;

						// Appel du WS spécifique Automation
						$response = $this->request('ws/wsAutomationsync.asmx', 'UpdateContact', $wsDatas, true, true);

						// En cas de succès, il doit retourner l'identifiant INES du contact
						if ( !isset($response['UpdateContactResult']) || $response['UpdateContactResult'] != $wsDatas['contact']['InternalRef']) {
							return false;
						}
					}
				}
			}

            // Si demandé, on ajoute un webLead dans INES
            // (utile lorsqu'un contact est mis à jour puis poussé vers INES via une action ATMT)
            if ($addLeadWhenUpdating) {
                $inesConfig = $this->getInesSyncConfig();
    			if (isset($inesConfig['LeadRef']) && $inesConfig['LeadRef'] >= 0) {
    				$this->addLeadToInesContact(
    					$internalContactRef,
    					$internalCompanyRef,
                        $clientWithContact['contact']['PrimaryMailAddress'],
    					$inesConfig['LeadRef']
    				);
    			}
            }
		}

		// Traitement des custom fields INES, s'il y en a
		$concepts = array('contact', 'client');
		foreach($concepts as $concept) {

			if (empty($mappedDatas[$concept]['customFields'])) {
				continue;
			}

			$inesRef = ($concept == 'contact') ? $internalContactRef : $internalCompanyRef;
			$inesRefKey = ($concept == 'contact') ? 'ctRef' : 'clRef';
			$wsConcept = ($concept == 'contact') ? 'Contact' : 'Company';

			// Lecture, via WS, des champs actuels chez INES
			$currentCustomFields = $this->getCurrentCustomFields($concept, $inesRef);

			// Parcours des champs Automation à mettre à jour
			foreach($mappedDatas[$concept]['customFields'] as $inesFieldKey => $fieldValue) {

				$datas = array(
					$inesRefKey => $inesRef,
					'chdefRef' => $inesFieldKey,
					'chpValue' => $fieldValue
				);

				$ws = false;

				// Si le champ n'exite pas encore chez INES : INSERT
				if ( !isset($currentCustomFields[$inesFieldKey])) {
					$ws = 'Insert'.$wsConcept.'CF';
					$datas['chvLies'] = 0;
					$datas['chvGroupeAssoc'] = 0;
				}
				// Si le champ existe déjà et que la valeur a changé : UPDATE
				else if ($currentCustomFields[$inesFieldKey]['chpValue'] != $fieldValue) {

					// La mise à jour est ignorée dans le cas d'un champ non écrasable
					$isProtectedField = in_array($inesFieldKey, $inesProtectedFields[$concept]);
					if ( !$isProtectedField) {
						$ws = 'Update'.$wsConcept.'CF';

						// La mise à jour d'un champ fait référence à son identifiant INES, lu précédemment
						$datas['chpRef'] = $currentCustomFields[$inesFieldKey]['chpRef'];
					}
				}

				if ($ws) {
					// Appel WS pour créer ou mettre à jour un champ custom
					$response = $this->request('ws/wscf.asmx', $ws, $datas, true, true);
					if ( !isset($response[$ws.'Result'])) {
						return false;
					}
				}
			}
		}

		return true;
	}


	/**
	 * Création chez INES d'un weblead lié à un contact
	 *
	 * @param 	int 			$internalContactRef
	 * @param 	int 			$internalCompanyRef
	 * @param 	string 			$email
	 * @param 	int 			$LeadRef	// Canal de lead, défini dans la config INES
	 *
	 * @return 	bool
	 */
	public function addLeadToInesContact($internalContactRef, $internalCompanyRef, $email, $leadRef)
	{
        try {
    		$response = $this->request('ws/wsAutomationsync.asmx', 'AddLead', array(
    			'info' => array(
    				'ClRef' => $internalCompanyRef,
    				'CtRef' => $internalContactRef,
    				'MailExpe' => $email,
    				'DescriptionCourte' => "Lead créé par Automation",
    				'ReclaDescDetail' => '',
    				'FileRef' => $leadRef,
    				'CriticiteRef' => 0,
    				'TypeRef' => 0,
    				'EtatRef' => 0,
    				'OrigineRef' => 0,
    				'DossierRef' => 0,
    				'CampagneRef' => 0,
    				'ArticleRef' => 0,
    				'ReclaMere' => 0,
    				'Propietaire' => 0,
    				'Gestionnaire' => 0
    			)
    		), true, true);

            return isset($response['AddLeadResult']);
        }
        catch (\Exception $e) {
            $this->integration->logIntegrationError($e);
            return false;
        }
	}


    /**
     * Supprime un contact chez INES (en réalité le flag comme "ne plus synchroniser")
     *
     * @param 	int  $inesRef    // Référence INES d'un contact
     *
     * @return 	bool
     */
    public function deleteContact($inesRef)
    {
        $response = $this->request('ws/wsAutomationsync.asmx', 'DeleteAutomationContact', array(
            'InesRef' => $inesRef
        ), true, true);

        return (isset($response['DeleteAutomationContactResult']) && $response['DeleteAutomationContactResult'] == 'Success');
    }


	/**
	 * Recherche chez INES un contact d'après son ID
	 *
	 * @param 	int 			$internalContactRef
	 *
	 * @return 	array | false
	 */
	public function getContactFromInes($internalContactRef)
	{
		$response = $this->request('ws/wsicm.asmx', 'GetContact', array(
			'reference' => $internalContactRef
		), true, true);

		if (isset($response['GetContactResult']['InternalRef']) &&
			$response['GetContactResult']['InternalRef'] == $internalContactRef
		){
			return $response['GetContactResult'];
		}

		return false;
	}


	/**
	 * Recherche chez INES d'une société (=client) d'après son ID
	 *
	 * @param 	int 			$internalCompanyRef
	 *
	 * @return 	array | false
	 */
	public function getClientFromInes($internalCompanyRef)
	{
		$response = $this->request('ws/wsicm.asmx', 'GetClient', array(
			'reference' => $internalCompanyRef
		), true, true);

		if (isset($response['GetClientResult']['InternalRef']) &&
			$response['GetClientResult']['InternalRef'] == $internalCompanyRef
		){
			return $response['GetClientResult'];
		}

		return false;
	}


	/**
	 * Recherche, à partir du mapping des champs, les champs Automation qui correspondent à une liste de champs INES
	 *
	 * @param 	array 	$inesFieldsKeys
	 *
	 * @return 	array 	Liste des identifiants des champs ATMT trouvés
	 */
	public function getAtmtFieldsKeysFromInesFieldsKeys($inesFieldsKeys)
	{
		$atmtFields = array();
		$mapping = $this->integration->getMapping();

		foreach($mapping as $mappingItem) {

			$inesFieldKey = $mappingItem['inesFieldKey'];

			if (in_array($inesFieldKey, $inesFieldsKeys)) {
				$atmtFields[$inesFieldKey] = $mappingItem['atmtFieldKey'];
			}
		}
		return $atmtFields;
	}


	/**
	 * Retourne les champs custom déjà présent chez INES pour un contact ou un client/société
	 *
	 * @param 	array 	$concept 	// contact | client alias company
	 * @param 	array 	$inesRef	// contactID ou clientID
	 *
	 * @return 	array | false
	 */
	public function getCurrentCustomFields($concept, $inesRef)
	{
		$concept = ucfirst($concept);
		if ($concept == 'Client') {
			$concept = 'Company';
		}

		// Appel WS : Lecture des champs
		$response = $this->request('ws/wscf.asmx', 'Get'.$concept.'CF', array('reference' => $inesRef), true, true);

		if ( !isset($response['Get'.$concept.'CFResult']['Values'])) {
			return false;
		}

		$customFields = array();
		$values = $response['Get'.$concept.'CFResult']['Values'];
		if ( !empty($values)) {
			foreach($values as $value_item) {

				// Dans le cas où plusieurs valeurs existent pour un seul champ, on s'intéresse au dernier élément uniquement
				if ( !isset($value_item['DefinitionRef'])) {
					$value_item = end($value_item);
				}

				$chdefRef = $value_item['DefinitionRef'];

				$customFields[$chdefRef] = array(
					'chpRef' => $value_item['Ref'],
					'chpValue' => $value_item['Value']
				);
			}
		}

		return $customFields;
	}


	/**
	 * Retourne un ID de session, nécessaire aux requêtes aux web-services
	 * Utilise celui stocké en session PHP s'il existe
	 * Sinon en demande un à INES (via le WS)
	 *
	 * @return	int (id de session)
	 * @throws 	ApiErrorException
	 */
	protected function getSessionID()
	{
		// Si une session existe déjà, on l'utilise
		$sessionID = $this->integration->getWebServiceCurrentSessionID();
		if ( !$sessionID) {

            $this->integration->log('Refresh session ID');

			// Sinon on en demande un
			$args = array(
				'request' => $this->integration->getDecryptedApiKeys()
			);

			$response = $this->request('wslogin/login.asmx', 'authenticationWs', $args, false);

			if (
				is_object($response) &&
				isset($response->authenticationWsResult->codeReturn) &&
				$response->authenticationWsResult->codeReturn == 'ok'
			){
				$sessionID = $response->authenticationWsResult->idSession;

				// Et on le mémorise pour plus tard
				$this->integration->setWebServiceCurrentSessionID($sessionID);
			}
			else {
				throw new ApiErrorException("INES WS : Can't get session ID");
			}
		}

		return $sessionID;
	}


	/**
	 * Lecture de la configuration de la synchro définie dans le CRM INES : champs customs à mapper, canal de leads à utiliser, type de société à utiliser
	 */
	protected function getInesSyncConfig()
	{
		$syncConfig = $this->integration->getCurrentSyncConfig();
		if ( !$syncConfig) {

            $this->integration->log('Refresh sync config');

			// Appel du WS
			$response = $this->request('Ws/WSAutomationSync.asmx', 'GetSyncInfo', array(), true);
			$results = isset($response->GetSyncInfoResult) ? $response->GetSyncInfoResult : false;
			if ($results === false) {
				throw new ApiErrorException("INES WS : Can't get sync config");
			}

            $companyCustomFields = json_decode(json_encode($results->CompanyCustomFields), true);
            $companyCustomFields = isset($companyCustomFields['CustomFieldToAuto']) ? $companyCustomFields['CustomFieldToAuto'] : [];

            $contactCustomFields = json_decode(json_encode($results->ContactCustomFields), true);
            $contactCustomFields = isset($contactCustomFields['CustomFieldToAuto']) ? $contactCustomFields['CustomFieldToAuto'] : [];

			// Canal de lead, type de société et champs custom
			$syncConfig = array(
				'LeadRef' => isset($results->LeadRef) ? $results->LeadRef : 0,
				'SocieteType' => isset($results->SocieteType) ? $results->SocieteType : 0,
				'CompanyCustomFields' => $companyCustomFields,
				'ContactCustomFields' => $contactCustomFields,
                'ValuesFromWS' => []
			);


            ///////// Lecture de toutes les clés / valeurs via les WS

            // Toutes les clés / valeurs pour TYPE CONTACT
            $response = $this->request('ws/wsicm.asmx', 'GetTypeContactList', array(), true, true);
            if (!isset($response['GetTypeContactListResult']['ContactTypeInfo'])) {
                throw new ApiErrorException("INES WS GetTypeContactList failed.");
            }
            $items = $response['GetTypeContactListResult']['ContactTypeInfo'];
            $values = [];
            // Tri par Order puis Description
            usort($items, function ($a, $b) {
                $isAsupB = ($a['Order'] == $b['Order']) ? ($a['Description'] > $b['Description']) : ($a['Order'] > $b['Order']);
                return $isAsupB ? 1 : -1;
            });
            foreach($items as $item) {
                $values[ $item['InternalRef'] ] = $item['Description'];
            }
            $syncConfig['ValuesFromWS']['GetTypeContactList'] = $values;

            // Toutes les clés / valeurs pour TYPE CLIENT
            $response = $this->request('ws/wsicm.asmx', 'GetTypeClientList', array(), true, true);
            if (!isset($response['GetTypeClientListResult']['ClientTypeInfo'])) {
                throw new ApiErrorException("INES WS GetTypeClientList failed.");
            }
            $items = $response['GetTypeClientListResult']['ClientTypeInfo'];
            // Tri par Order puis Description
            usort($items, function ($a, $b) {
                $isAsupB = ($a['Order'] == $b['Order']) ? ($a['Description'] > $b['Description']) : ($a['Order'] > $b['Order']);
                return $isAsupB ? 1 : -1;
            });
            $values = [];
            foreach($items as $item) {
                $values[ $item['InternalRef'] ] = $item['Description'];
            }
            $syncConfig['ValuesFromWS']['GetTypeClientList'] = $values;

            // Toutes les clés / valeurs pour ORIGIN
            $response = $this->request('ws/wsicm.asmx', 'GetOriginList', array(), true, true);
            if (!isset($response['GetOriginListResult']['OriginInfo'])) {
                throw new ApiErrorException("INES WS GetOriginList failed.");
            }
            $items = $response['GetOriginListResult']['OriginInfo'];
            $values = [];
            foreach($items as $item) {
                $values[ $item['InternalRef'] ] = $item['Description'];
            }
            $syncConfig['ValuesFromWS']['GetOriginList'] = $values;

            // Toutes les clés / valeurs pour USER REF
            $response = $this->request('ws/wsicm.asmx', 'GetUserInfoFromUserRef', array(), true, true);
            if (!isset($response['GetUserInfoFromUserRefResult']['UserInfoRH'])) {
                throw new ApiErrorException("INES WS GetUserInfoFromUserRef failed.");
            }
            $items = $response['GetUserInfoFromUserRefResult']['UserInfoRH'];
            $values = [];
            foreach($items as $item) {
                $values[ $item['UserRef'] ] = $this->switchFirstNameLastName($item['Name']);
            }
            asort($values);
            $syncConfig['ValuesFromWS']['GetUserInfoFromUserRef'] = $values;

            // Toutes les clés / valeurs pour RH REF
            $response = $this->request('ws/wsicm.asmx', 'GetUserInfoFromRHRef', array(), true, true);
            if (!isset($response['GetUserInfoFromRHRefResult']['UserInfoRH'])) {
                throw new ApiErrorException("INES WS GetUserInfoFromRHRef failed.");
            }
            $items = $response['GetUserInfoFromRHRefResult']['UserInfoRH'];
            $values = [];
            foreach($items as $item) {
                $values[ $item['RHRef'] ] = $this->switchFirstNameLastName($item['Name']);
            }
            asort($values);
            $syncConfig['ValuesFromWS']['GetUserInfoFromRHRef'] = $values;

			// On mémorise la config obtenue pour les appels suivants
			$this->integration->setCurrentSyncConfig($syncConfig);

            // A chaque fois que la config est régénérée, on vérifie que les champs customs sont bien à jour dans ATMT
            $this->integration->updateAtmtCustomFieldsDefinitions();
		}

		return $syncConfig;
	}



	/**
	 * Requête aux web-services INES
	 *
	 * @param 	string	$ws_relative_url	Exemple : wslogin/login.asmx
	 * @param	string	$method				Méthode à appeler sur l'objet SOAP. Exemple : 'authenticationWs'
	 * @param	array 	$args				Paramètres à transmettre à la méthode
	 * @param 	bool 	$auth_needed		Mettre true si un ID de session est requis
	 * @param 	bool	$return_as_array	Mettre true pour convertir l'Object de retour en Array
	 *
	 * @return 	Object 	(réponse de l'API)
	 *
	 * @throws Exception
	 */
    protected function request($ws_relative_url, $method, $args, $auth_needed = true, $return_as_array = false)
	{
		// URL du client
		$client_url = ($auth_needed ? 'https' : 'http') . '://webservices.inescrm.com/';
		$client_url .= ltrim($ws_relative_url, '/') . '?wsdl';

		// Entête SOAP avec un ID de session si cette requête exige une authentification
		if ($auth_needed) {

			$sessionID = $this->getSessionID();

			$settings = array(
				'soapHeader' => array(
					'namespace' => 'http://webservice.ines.fr',
					'name' => 'SessionID',
					'datas' => array('ID' => $sessionID)
				)
			);
		}
		else {
			$settings = false;
		}

		// Appel SOAP au web-service
		try {
			$response = $this->integration->makeRequest($client_url, $args, $method, $settings);
		} catch (\Exception $e) {

			// En cas d'échec d'une requête nécessitant un sessionID, il est possible que celui-ci ait expiré
			// Donc on tente de rafraîchir cet ID avant un 2ème essai
			if ($auth_needed) {
				try {
					$this->refreshSessionID();
					$response = $this->integration->makeRequest($client_url, $args, $method, $settings);
				} catch (\Exception $e) {
					throw $e;
				}
			}
			else {
				throw $e;
			}
		}

		// Conversion en Array si demandé
		if ($return_as_array) {
			$response = json_decode(json_encode($response), true);
		}

		return $response;
	}


	/**
	 * Retourne les paramètres minimaux pour requêter un WS INES utilisant la notion de client
	 *
	 * @return 	Array 	Liste des champs
	 */
	protected function getClientTemplate()
	{
		return array(
            'Confidentiality' => 'Undefined',
            'CompanyName' => '',
            'Type' => 0, /* à renseigner d'après config INES : type de société */
            'Service' => '',
            'Address1' => '',
            'Address2' => '',
            'ZipCode' => '',
            'City' => '',
            'State' => '',
            'Country' => '',
            'Phone' => '',
            'Fax' => '',
            'Website' => '',
            'Comments' => '',
            'Manager' => 0,
            'SalesResponsable' => 0,
            'TechnicalResponsable' => 0,
            'CreationDate' => date("Y-m-d\TH:i:s"),
            'ModifiedDate' => date("Y-m-d\TH:i:s"),
            'Origin' => 0,
            'CustomerNumber' => 0,
            'CompanyTaxCode' => '',
            'VatTax' => 0,
            'Bank' => '',
            'BankAccount' => '',
            'PaymentMethod' => '',
            'PaymentMethodRef' => 1, /* OBLIGATOIRE ET NON NUL, SINON ERROR */
            'Discount' => 0,
            'HeadQuarter' => 0,
            'Language' => '',
            'Activity' => '',
            'AccountingCode' => '',
            'Scoring' => '',
            'Remainder' => 0,
            'MaxRemainder' => 0,
            'Moral' => 0,
            'Folder' => 0,
            'Currency' => '',
            'BankReference' => 0,
            'TaxType' => 0,
            'VatTaxValue' => 0,
            'Creator' => 0,
            'Delivery' => 0,
            'Billing' => 0,
            'IsNew' => true,
            'AutomationRef' => 0, /* ne pas renseigner car le concept de société ATMT n'est pas géré */
            'InternalRef' => 0
		);
	}


	/**
	 * Retourne les paramètres minimaux pour requêter un WS INES utilisant la notion de contact
	 *
	 * @return 	Array 	Liste des champs
	 */
	protected function getContactTemplate()
	{
		return array(
            'Author' => 0,
            'BusinessAddress' => '',
            'BussinesTelephone' => '',
            'City' => '',
            'Comment' => "",
            'CompanyRef' => 0,
            'Confidentiality' => 'Undefined',
            'Country' => '',
            'CreationDate' => date("Y-m-d\TH:i:s"),
            'DateOfBirth' => date("Y-m-d\TH:i:s"),
            'Fax' => '',
            'FirstName' => '',
            'Function' => '',
            'Genre' => '',
            'HomeAddress' => '',
            'HomeTelephone' => '',
            'IsNew' => true,
            'Language' => '',
            'LastName' => '',
            'MobilePhone' => '',
            'ModificationDate' => date("Y-m-d\TH:i:s"),
            'PrimaryMailAddress' => '',
            'Rang' => 'Principal',
            'SecondaryMailAddress' => '',
            'Service' => '',
            'Type' => 0,
            'State' => '',
            'ZipCode' => '',
            'Desabo' => '',
            'NPai' => '',
            'InternalRef' => 0,
            'AutomationRef' => 0,
            'Scoring' => 0
		);
	}


	/**
	 * Retourne les paramètres minimaux pour requêter un WS INES utilisant la notion de client avec des contact
	 *
	 * @param 	int		$nbContacts			Nombre de contacts à créer pour le client
	 *
	 * @return 	Array 	Liste des champs
	 */
	protected function getClientWithContactsTemplate($nbContacts = 1)
	{
		// Structure pour un client
		$datas = array(
			'client' => $this->getClientTemplate()
		);

		// Préparation de la liste des contacts de ce client
		$datas['client']['Contacts'] = array(
			'ContactInfoAuto' => array()
		);

		// Remplissage du nombre de contact demandés
		$contactTemplate = $this->getContactTemplate();
		for($i=0; $i<$nbContacts; $i++) {
			array_push(
				$datas['client']['Contacts']['ContactInfoAuto'],
				$contactTemplate
			);
		}

		return $datas;
	}


    /**
     * Détecte le nom et prénom à partir d'une chaîne fusionnée "Prénom NOM", puis les interverti
     *
     * @param 	string   $fullname
     *
     * @return 	string
     */
    protected function switchFirstNameLastName($fullname)
    {
        $pos = strlen($fullname);
        do {
            $pos--;
            $char = substr($fullname, $pos, 1);
        } while($pos > 0 && $char == strtoupper($char));

        if ($pos > 0) {
            $last_name = trim(substr($fullname, $pos + 1));
            $first_name = trim(substr($fullname, 0, $pos + 1));
        }
        else {
            $last_name = $fullname;
            $first_name = "";
        }
        return $last_name.' '.$first_name;
    }
}
