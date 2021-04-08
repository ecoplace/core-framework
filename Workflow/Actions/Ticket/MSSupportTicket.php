<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Workflow\Actions\Ticket;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

class MSSupportTicket extends WorkflowAction
{
    public static function getId()
    {
        return 'reply';
    }

    public static function getDescription()
    {
        return "MS Support Ticket";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::TICKET;
    }

    public static function getOptions(ContainerInterface $container)
    {
        return [];
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        if($entity instanceof Ticket && $entity->getIsTrashed())
            return;
        if($entity instanceof Ticket) {
            $placeHolderValues   = $container->get('email.service')->getTicketPlaceholderValues($entity);

            $assignedTo = $placeHolderValues['ticket.agentName'] ? $placeHolderValues['ticket.agentName'] : '(none)';
            $post_data = '{
              "@context": "https://schema.org/extensions",
              "@type": "MessageCard",
              "themeColor": "ff5a5f",
              "summary": "Ticket Created",
              "title": "#'.$placeHolderValues['ticket.id'].' '.$placeHolderValues['ticket.subject'].'",
              "sections": [
                {
                  "activityTitle": "'.$placeHolderValues['ticket.customerName'].'",
                  "activitySubtitle": "'.$placeHolderValues['ticket.createdAt'].'",
                  "activityImage": "https://help.charitybay.org/assets/customer.png",
                  "facts": [
                    {
                      "name": "Source:",
                      "value": "'.$placeHolderValues['ticket.source'].'"
                    },
                    {
                      "name": "Priority:",
                      "value": "'.$placeHolderValues['ticket.priority'].'"
                    },
                    {
                      "name": "Assigned to:",
                      "value": "'.$assignedTo.'"
                    },
                    {
                      "name": "Status:",
                      "value": "'.$placeHolderValues['ticket.status'].'"
                    }
                  ],
                  "text": "'.$placeHolderValues['ticket.message'].'"
                }
              ],
              "potentialAction": [
                {
                  "@type": "ActionCard",
                  "name": "Send reply",
                  "inputs": [
                    {
                      "@type": "TextInput",
                      "id": "feedback",
                      "isMultiline": true,
                      "isRequired": true,
                      "title": "Write your response here."
                    },
                    {
                      "@type": "TextInput",
                      "id": "email",
                      "isRequired": true,
                      "title": "Your email address"
                    }
                  ],
                  "actions": [
                    {
                      "@type": "HttpPOST",
                      "name": "Send reply",
                      "isPrimary": true,
                      "target": "https://help.charitybay.org/api/v1/ticket/'.$placeHolderValues['ticket.id'].'/thread",
                      "body": "threadType=reply&message={{feedback.value}}&actAsType=agent&actAsEmail={{email.value}}",
                      "bodyContentType": "application/x-www-form-urlencoded",
                      "headers": [
                            { "name": "Authorization", "value": "" },
                            {
                                "name": "X-Custom-Auth",
                                "value": "basic WAQZ0CLACHOJGH5GUZF7HIPTPOFAJ82SFQWIUNJ1RJJNA2N1EYJUS2AVAM4UAWIP"
                            }
                      ]
                    }
                  ]
                },
                {
                  "@type": "OpenUri",
                  "name": "View ticket",
                  "targets": [
                    { "os": "default", "uri": "https://help.charitybay.org/en/member/ticket/view/'.$placeHolderValues['ticket.id'].'" }
                  ]
                }
              ]
            }';

            $client = new Client();
            try {
                $client->request('POST', $value, [
                    'body' => $post_data
                ]);
            } catch (GuzzleException $e) {
               dump($e->getMessage());
            }

        }
    }

}
