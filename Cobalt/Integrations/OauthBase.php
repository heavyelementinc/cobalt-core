<?php
namespace Cobalt\Integrations;

use Auth\UserCRUD;
use DateTime;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\UTCDateTime;

abstract class OauthBase extends Base {

    /**
     * Returns a clickable button
     * @param null|string $state
     * @param int $redirect_uri
     * @return string
     */
    function html_oauth_button($state = null, int $redirect_uri = 0):string {
        
        return view("/Cobalt/Integrations/templates/oauth/button.html", [
            'config' => $this->config,
            'name' => $this->config->name,
            'publicName' => $this->config->publicName,
            'icon' => $this->config->icon,
            'link' => $this->get_oauth_link($state, $redirect_uri),
        ]);
    }

    function get_oauth_link($state = null, int $redirect_uri = 0) {
        $response_type = "code";
        $query = [
            'client_id' => (string)$this->config->client_id,
            'redirect_uri' => $this->config->redirect_uris[$redirect_uri],
            'response_type' => (string)$response_type,
            'access_type' => (string)$this->config->access_type,
        ];
        if(!empty($this->config->scope->getValue())) {
            $delimiter = $this->config->getDirective("scope", "delimiter") ?? " ";
            $query['scope'] = implode($delimiter, $this->config->scope->getValue());
        }

        if($state !== null) $query['state'] = $state;
        else $query['state'] = $_SERVER['REDIRECT_URL'];
        return $this->config->auth_uri."?".http_build_query($query);
    }

    /**
     * Since OAuth tokens are stored with the user account, we
     * need a function that returns the current token.
     * @return array 
     */
    // abstract function oauth_fetch_token(): array;

    /**
     * This function is called when finalizing an OAuth sign-in
     * @return string 
     */
    function oauth_receive(array $result): int {
        if(session() === null) throw new Unauthorized("You must be logged in.", true);
        
        // Error handling
        if(isset($result['error'])) {
            $errors = $this->oauth_errors();
            if(!key_exists($result['error'], $errors)) throw new BadRequest("An unknown error occurred", true);
            $errors[$result['error']]['callback']($result['error']);
            throw new BadRequest($errors[$result['error']]['message']($result['error']), true);
        }
        
        // Instance our user database
        $crud = new UserCRUD();
        $processed = $this->oauth_fetch_credentials($result);
        if(!key_exists('details', $processed) || !key_exists('expiration', $processed)) throw new Exception("Missing expected key from response processor");
        $result = $crud->store_integration_credentials(session()['_id'], $this->config->tokenName, $processed['details'], $processed['expiration']);
        return $result;
    }

    /**
     * Return a list of possible errors as keys. Each value
     * should be an array with a 'callback' delta function
     * and a 'message' delta function. They must accept either
     * 0 or 1 parameter.
     * @return array 
     */
    abstract function oauth_errors():array;

    /**
     * The result of this function must include the following keys:
     *   * 'details' - The response from the Oauth Gateway
     *   * 'expiration' - A DateTime set to 'now' + the expiration time of the token (or null)
     * 
     * !!! OTHER KEYS ARE IGNORED !!!
     * 
     * @param mixed $authcode
     * @return array 
     */
    public function oauth_fetch_credentials(mixed $authcode): array {
        $r = $this->fetch(
            "POST",
            (string)$this->config->token_uri,
            [
                'client_id' => (string)$this->config->client_id,
                'client_secret' => (string)$this->config->client_secret,
                'code' => $authcode['code'],
                'grant_type' => "authorization_code",
                'redirect_uri' => (string)$this->config->redirect_uris[0],
            ],
            [],
            false
        );

        $response = $r['response'];

        $time = time();
        $time += $response['expires_in'];
        $date = new UTCDateTime(new DateTime(date("c",$time)));
        return [
            'details' => $response,
            'expiration' => $date
        ];
    }

    /** This function should always return valid oauth credentials, either from
     * the session() or fetched from the API.
     */
    // abstract public function get_oauth_credentials(): array;

    public function fetch(string $method, string $action, array $data = [], array $headers = [], bool $authenticate = true):array {
        
        // $name = $this->config->tokenName;
        // $credentials = session("integrations.$name");
        
        // $time = time();
        // $time += $response['expires_in'];
        // $crud = new UserCRUD();
        // $crud->update_integration_credentials(session('_id'), $this->config->tokenName, $response, new UTCDateTime(new DateTime(date("c", $time))));
        return parent::fetch($method, $action, $data, $headers, $authenticate);
    }

    // public function oauth_fetch(string $method, $action, array $data = [], array $headers = [], bool $authenticate = true): array {
    //     $response = $this->get_oauth_credentials();

    // }
}
