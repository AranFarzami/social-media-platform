<?php

require_once __DIR__ . "/../vendor/autoload.php";

require_once __DIR__ . "/../config/database.php";


use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use React\Socket\SocketServer;
use React\EventLoop\Loop;


class ChatServer implements MessageComponentInterface
{

    protected $clients;

    protected $users = [];


    public function __construct()
    {

        $this->clients =
            new \SplObjectStorage();

        echo "Chat server started...\n";

        echo "Waiting for connections...\n";

    }


    

    public function onOpen(
        ConnectionInterface $conn
    ) {

        $this->clients->attach($conn);


        echo
            "New connection: "
            .
            $conn->resourceId
            .
            PHP_EOL;

    }


    

    public function onMessage(
        ConnectionInterface $from,
        $msg
    ) {

        global $pdo;


        $data =
            json_decode(
                $msg,
                true
            );


        if (
            !is_array($data)
        ) {

            return;

        }


        
        if (
            ($data['type'] ?? '')
            ===
            'join'
        ) {

            $user_id =
                (int)($data['user_id'] ?? 0);


            if (
                $user_id <= 0
            ) {

                return;

            }


            $this->users[
                $user_id
            ] = $from;


            $from->user_id =
                $user_id;


            echo
                "User "
                .
                $user_id
                .
                " connected"
                .
                PHP_EOL;


            return;

        }


        

        if (
            ($data['type'] ?? '')
            ===
            'message'
        ) {

            $receiver_id =
                (int)($data['receiver_id'] ?? 0);


            $message =
                trim(
                    $data['message'] ?? ''
                );


            $sender_id =
                (int)(
                    $from->user_id
                    ??
                    0
                );


            

            if (
                $sender_id <= 0
                ||
                $receiver_id <= 0
                ||
                $receiver_id === $sender_id
                ||
                $message === ''
            ) {

                return;

            }


            if (
                mb_strlen($message)
                >
                5000
            ) {

                return;

            }


            

            $stmt =
                $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $receiver_id
            ]);


            if (
                !$stmt->fetch()
            ) {

                return;

            }


           

            $stmt =
                $pdo->prepare("
                    INSERT INTO messages
                    (
                        sender_id,
                        receiver_id,
                        message
                    )
                    VALUES (?, ?, ?)
                ");


            $stmt->execute([

                $sender_id,

                $receiver_id,

                $message

            ]);


           

            $message_id =
                (int)$pdo->lastInsertId();


           
            $stmt =
                $pdo->prepare("
                    SELECT
                        id,
                        sender_id,
                        receiver_id,
                        message,
                        created_at
                    FROM messages
                    WHERE id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $message_id
            ]);


            $new_message =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (
                !$new_message
            ) {

                return;

            }


            

            $payload =
                json_encode(

                    array_merge(

                        [
                            "type" => "message"
                        ],

                        $new_message

                    ),

                    JSON_UNESCAPED_UNICODE

                );


            $from->send(
                $payload
            );


        
            if (
                isset(
                    $this->users[
                        $receiver_id
                    ]
                )
            ) {

                $receiver =
                    $this->users[
                        $receiver_id
                    ];


                $receiver->send(
                    $payload
                );

            }


            echo
                "Message "
                .
                $message_id
                .
                ": "
                .
                $sender_id
                .
                " -> "
                .
                $receiver_id
                .
                PHP_EOL;

        }

    }




    public function onClose(
        ConnectionInterface $conn
    ) {

        $this->clients->detach(
            $conn
        );


        if (
            isset(
                $conn->user_id
            )
        ) {

            $user_id =
                (int)$conn->user_id;


            if (
                isset(
                    $this->users[
                        $user_id
                    ]
                )
                &&
                $this->users[
                    $user_id
                ] === $conn
            ) {

                unset(
                    $this->users[
                        $user_id
                    ]
                );

            }


            echo
                "User "
                .
                $user_id
                .
                " disconnected"
                .
                PHP_EOL;

        }

    }



    public function onError(
        ConnectionInterface $conn,
        \Exception $e
    ) {

        echo
            "WebSocket Error: "
            .
            $e->getMessage()
            .
            PHP_EOL;


        $conn->close();

    }

}




$chatServer =
    new ChatServer();


$server =
    new IoServer(

        new HttpServer(

            new WsServer(
                $chatServer
            )

        ),

        new SocketServer(
            "0.0.0.0:8080"
        ),

        Loop::get()

    );


echo
    "WebSocket server listening on port 8080"
    .
    PHP_EOL;


$server->run();