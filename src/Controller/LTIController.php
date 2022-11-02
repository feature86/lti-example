<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LTIController extends Controller
{
    public function index(Request $req)
    {
        // copy some url paramters to enabled pre filled form urls
        $id = $req->get('id', '');
        return $this->render('lti/index.html.twig', [
            'consumerKey' => $req->get('consumerKey', ''),
            'sharedSecret' => $req->get('sharedSecret', ''),
            'launchUrl' => $req->get('launchUrl', 'https://atlasdev.im-c.cloud/api/login/lti'),
            'customCompanyName' => $req->get('customCompanyName', ''),
            'customCompanyId' => $req->get('customCompanyId', ''),
            'customIsCompanySuperior' => $req->get('customIsCompanySuperior', ''),
            'customAssignedCompanyIds' => $req->get('customAssignedCompanyIds', ''),
            'customIsSuperior' => $req->get('customIsSuperior', ''),
            'customWorkspaceId' => $req->get('customWorkspaceId', ''),
            'customCatalogId' => $req->get('customCatalogId', ''),
            'customSeriesId' => $req->get('customSeriesId', ''),
            'customTrackId' => $req->get('customTrackId', ''),
            'launchPresentationLocale' => $req->get('launchPresentationLocale', ''),
            'id' => $id === 'random' ? rand(10000, 99999) : $id,
        ]);
    }

    public function form(Request $req)
    {
        $launchUrl = $req->get('launchUrl');
        $parameters = [
            // type and verion
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',

            // nique id referencing the link, or "placement", of the app in the consumer. If an app was added twice to the same class, each placement would send a different id, and should be considered a unique "launch". For example, if the provider were a chat room app, then each resource_link_id would be a separate room.
            'resource_link_id' => 'lti-demo',

            // user id used to uniquely identify the user
            'user_id' => $req->get('id'),

            // OAuth paramters
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version' => '1.0',
            'oauth_consumer_key' => $req->get('consumerKey'),
            'oauth_timestamp' => time(),
            'oauth_callback' => 'about:blank',
        ];

        // optional paramters
        $firstname = $req->get('firstname', null);
        if (!empty($firstname)) {
            $parameters['lis_person_name_given'] = $firstname;
        }
        $lastname = $req->get('lastname', null);
        if (!empty($lastname)) {
            $parameters['lis_person_name_family'] = $lastname;
        }
        if (!empty($firstname) || !empty($lastname)) {
            $parameters['lis_person_name_full'] = trim($firstname.' '.$lastname);
        }
        $email = $req->get('email', null);
        if (!empty($email)) {
            $parameters['lis_person_contact_email_primary'] = $email;
        }
        $locale = $req->get('launchPresentationLocale', null);
        if (!empty($locale)) {
            $parameters['launch_presentation_locale'] = $locale;
        }
        $groups = $req->get('customGroups', null);
        if (!empty($groups)) {
            $parameters['custom_groups'] = $groups;
        }
        $team = $req->get('customTeam', null);
        if (!empty($team)) {
            $parameters['custom_team'] = $team;
        }
        
        $companyName = $req->get('customCompanyName', null);
        if (!empty($companyName)) {
            $parameters['custom_company_name'] = $companyName;
        }
        $companyId = $req->get('customCompanyId', null);
        if (!empty($companyId)) {
            $parameters['custom_company_id'] = $companyId;
        }
        $companyIsSuperior = $req->get('customIsCompanySuperior', null);
        if (!empty($companyIsSuperior)) {
            $parameters['custom_is_company_superior'] = $companyIsSuperior;
        }
        $assigendCompanyIds = $req->get('customAssignedCompanyIds', null);
        if (!empty($assigendCompanyIds)) {
            $parameters['custom_assigned_company_ids'] = $assigendCompanyIds;
        }
        
        $isSuperior = $req->get('customIsSuperior', null);
        if (!empty($isSuperior)) {
            $parameters['custom_is_superior'] = $isSuperior;
        }
        
        $workspaceId = $req->get('customWorkspaceId', null);
        if (!empty($workspaceId)) {
            $parameters['custom_workspace_id'] = $workspaceId;
        }
        
        $catalogId = $req->get('customCatalogId', null);
        if (!empty($catalogId)) {
            $parameters['custom_catalog_id'] = $catalogId;
        }
        $seriesId = $req->get('customSeriesId', null);
        if (!empty($seriesId)) {
            $parameters['custom_series_id'] = $seriesId;
        }
        
        $trackId = $req->get('customTrackId', null);
        if (!empty($trackId)) {
            $parameters['custom_track_id'] = $trackId;
        }

        $oauthSignature = $this->getOAuthSignature($parameters, $req->get('sharedSecret'), $req->get('launchUrl'));
        $parameters['oauth_signature'] = $oauthSignature;
        ksort($parameters);

        return $this->render('lti/form.html.twig', [
            'launchUrl' => $launchUrl,
            'parameters' => $parameters,
        ]);
    }

    private function getCleanUrl($url)
    {
        // Parse & add query params as base string parameters if they exists
        $url = parse_url($url);

        // Remove default ports
        $explicitPort = isset($url['port']) ? $url['port'] : null;
        if (('https' === $url['scheme'] && 443 === $explicitPort) || ('http' === $url['scheme'] && 80 === $explicitPort)) {
            $explicitPort = null;
        }
        // Remove query params from URL
        $url = sprintf('%s://%s%s%s', $url['scheme'], $url['host'], ($explicitPort ? ':'.$explicitPort : ''), isset($url['path']) ? $url['path'] : '');

        return $url;
    }

    private function getOAuthSignature(array $parameters, $secret, $url)
    {
        // Cleanup url
        $url = $this->getCleanUrl($url);

        // Build POST params array
        $params = [];
        $signature = null;
        foreach ($parameters as $key => $value) {
            $params[] = $key . "=" . rawurlencode($value);
        }
        sort($params);

        $base = 'POST&' . urlencode($url) . '&' . rawurlencode(implode('&', $params));
        $encodedSecret = urlencode($secret) . '&';
        return base64_encode(hash_hmac('sha1', $base, $encodedSecret, true));
    }
}
