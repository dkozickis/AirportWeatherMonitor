<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 08/02/17
 * Time: 11:50
 */

namespace AppBundle\Admin;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminInlineEditController extends Controller
{

    /**
     * @param Request $request
     * @Route("/edit")
     * @Method({"put"})
     */
    public function editAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $rep = $em->getRepository("AppBundle:MonitoredAirport");

        $entityID = $request->get('id');
        $entityParam = $request->get('param');
        $newValue = trim($request->get('value'));

        if (null === $newValue || empty($newValue)) {
            $newValue = null;
        }

        $result = $rep->createQueryBuilder('ma')
            ->update("AppBundle:MonitoredAirport", 'ma');

        if (null === $newValue || empty($newValue)) {
            dump("We null baby");
            $result->set("ma.".$entityParam, 'NULL');
        } else {
            $result->set("ma.".$entityParam, $newValue);
        }

        $result->where("ma.id = :entity_id")
            ->setParameter("entity_id", $entityID)
            ->getQuery()
            ->getResult();

        return new Response(json_encode($result));
    }

}