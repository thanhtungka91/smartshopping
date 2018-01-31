<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Core\Configure;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;


/**
 * Static content controller
 *
 * This controller will render views from Template/Pages/
 *
 * @link https://book.cakephp.org/3.0/en/controllers/pages-controller.html
 */
class CrawlersController extends AppController
{

    /**
     * Displays a view
     *
     * @param array ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Network\Exception\ForbiddenException When a directory traversal attempt.
     * @throws \Cake\Network\Exception\NotFoundException When the view file could not
     *   be found or \Cake\View\Exception\MissingTemplateException in debug mode.
     */
    public function index()
    {
        $client = new Client();
        $guzzleClient = new GuzzleClient(array(
            'timeout' => 60,
            'defaults' => ['verify' => false],
        ));

        $client->setClient($guzzleClient);
 
        $crawler = $client->request('GET', 'https://www.amazon.co.jp');

        $form = $crawler->selectButton('検索')->form();
        $crawler = $client->submit($form, array('field-keywords' => '8006643000928'));

        $crawler->filter('.a-link-normal.s-access-detail-page.s-color-twister-title-link.a-text-normal')->each(function ($node) {
            $client = new Client();
            $crawler = $client->click($node->link());
            // check if valid get link 
            print_r($client->getHistory()->current()->getUri());  //da lay duoc url 
            die(); 
        }); 
   

        try {
            $this->render();
        } catch (MissingTemplateException $exception) {
            if (Configure::read('debug')) {
                throw $exception;
            }
            throw new NotFoundException();
        }
    }
}
