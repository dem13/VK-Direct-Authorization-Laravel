<?php

namespace App\Http\Controllers;

use VK\Client\VKApiClient;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    /**
     * VK API Client
     * 
     * @var VK\Client\VKApiClient
     */
    private $vk;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->vk = new VKApiClient();
        $this->middleware('auth');
    }

    /**
     * Show the user conversations.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $conversations = $this->getConversations();

        return view('home',compact('conversations'));
    }

    /**
     * Get VK Conversations
     * 
     * @return array 
     */
    private function getConversations()
    {
        $accessToken = auth()->user()->access_token;

        $conversations = $this->vk->messages()->getConversations($accessToken,[
            'extended' => 1,
        ]);

       return $this->compactConversations($conversations);
    }

    /**
     * Convert each type of conversation to the same
     * 
     * @param  array $conversations 
     * @return array
     */
    private function compactConversations($conversations)
    {
        $compactedConversations = [];

        $profiles = $conversations['profiles'];

        $groups = $conversations['groups'];

        foreach($conversations['items'] as $item){
            $conversation = $item['conversation'];
        
            $id = $conversation['peer']['local_id'];
        
            $lastMessage = $item['last_message'];

            switch ($conversation['peer']['type']) {
                case 'user':
                    $user = $this->findElementById($profiles, $id);
                    
                    $name = "{$user['first_name']} {$user['last_name']}";
                    
                    $photo = $user['photo_50'];

                    break;

                case 'chat':
                    $chat = $conversation['chat_settings'];
                    
                    $name = $chat['title'];

                    $photo = isset($chat['photo'])?$chat['photo']['photo_50']:config('vk.default_image');

                    break;

                case 'group':
                    $group = $this->findElementById($groups, $id);
                    
                    $name = $group['name'];

                    $photo = $group['photo_50'];

                    break;

                default:
                    continue 2;
                    break;
            }

            array_push($compactedConversations, [
                'id' => $id,
                'name' => $name,
                'photo' => $photo,
                'last_message' => $lastMessage,
            ]);
        }

        return $compactedConversations;
    }

    /**
     * Find the element by the value of the key id in the provided array
     * 
     * @param  array $elements 
     * @param  mixed $id
     * @return array       
     */
    private function findElementById($elements, $id){
        foreach ($elements as $element) {
            if($element['id'] == $id)return $element;
        }
    }
}
