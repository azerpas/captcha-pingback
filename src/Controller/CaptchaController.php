<?php

namespace App\Controller;

use App\Entity\Captcha;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class CaptchaController extends AbstractController {
    /**
     * @Route("/", name="index", methods={"GET","POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function captcha(Request $request){
        $ip = $this->getIp($_SERVER);
        if(!$this->ipAuthorized($ip)){
            return new JsonResponse(["message"=>"Not authorized"], 403);
        }else{
            if($request->getMethod() === "POST"){
                if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                    try {
                        $data = json_decode($request->getContent(), true);
                        $request->request->replace(is_array($data) ? $data : array());
                    }catch (Exception $e) {
                        return new JsonResponse(["message"=>"Double check your body", "error"=>$e->getMessage()], 400);
                    }
                    $captcha = new Captcha();
                    try {
                        $captcha->setGivenId($data["id"]);
                        $captcha->setAnswer($data["answer"]);
                    }catch (Exception $e){
                        return new JsonResponse(["message"=>"Field might be missing inside your body", "error"=>$e->getMessage()], 400);
                    }
                    $captcha->setAddedAt(new DateTime());
                    try {
                        $manager = $this->getDoctrine()->getManager();
                        $manager->persist($captcha);
                        $manager->flush();
                        return new JsonResponse([], 201);
                    }catch (Exception $e) {
                        return new JsonResponse(["message"=>"Error while saving captcha object", "error"=>$e->getMessage()], 500);
                    }
                }else{
                    return new JsonResponse(["message"=>"Set your header to application/json"], 400);
                }
            }else {
                return new JsonResponse(["message"=>"Method not defined"], 400);
            }
        }
    }

    private function getIp($server): string{
        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            $ipAddresses = explode(',', $server['HTTP_X_FORWARDED_FOR']);
            return trim(end($ipAddresses));
        }
        else {
            return $server['REMOTE_ADDR'];
        }
    }

    private function ipAuthorized(string $ip): int{
        return preg_match($_ENV["WHITELISTED_IPS"], $ip, $matches);
    }
}
