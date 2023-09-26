<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/api/user', name: 'app_api_user')]
    public function index(SerializerInterface $serializer): JsonResponse
    {
        try {
            $users = $this->entityManager->getRepository(User::class)->findAll();
            if (!$users) {
                return new JsonResponse([
                    'status' => 204,
                    'msg' => "No records found",
                    'data' => []
                ]);
            }
            // Serialize the entire entity (all fields)
            $userData = $serializer->normalize($users);
            return new JsonResponse([
                'status' => 200,
                'msg' => "Records found",
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
    }
    #[Route('/api/user/create', name: 'app_api_user_create',methods: ['POST'])]
    public function create(Request $request,ValidatorInterface $validator,SerializerInterface $serializer):JsonResponse
    {
        try {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $comment = $request->request->get('comment');
            $client_id = $request->request->get('client_id');

            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPhone($phone);
            $user->setComment($comment);
            $user->setClientId($client_id);
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[$violation->getPropertyPath()][] = $violation->getMessage();
                }
                return new JsonResponse([
                    'status' => 422,
                    'msg' => "There is some validation error!",
                    'error' => $messages
                ]);
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            // Serialize the entire entity (all fields)
            $userData = $serializer->normalize($user);
            return new JsonResponse([
                'status' => 201,
                'msg' => "User created successfully!",
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }

    }
    #[Route('/api/user/show/{id}', name: 'app_api_user_show')]
    public function show($id,SerializerInterface $serializer):JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);
            if(!$user){
                return new JsonResponse([
                    'status' => 204,
                    'msg' => "User does not exist.",
                    'data' => []
                ]);
            }
            // Serialize the entire entity (all fields)
            $userData = $serializer->normalize($user);
            return new JsonResponse([
                'status'=> 200,
                'msg' => "User detail fetched successfully!",
                'data' => $userData,
            ]);
        }catch (\Exception $e){
            return new JsonResponse([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
    }
    #[Route('/api/user/edit', name: 'app_api_user_edit',methods: ['POST'])]
    public function edit(Request $request,ValidatorInterface $validator,SerializerInterface $serializer):JsonResponse
    {
        try {
            $id = $request->request->get('id');
            if (!isset($id)) {
                return new JsonResponse([
                    'status'=> 422,
                    'msg' => "There is some validation error!",
                    'error' => ['id' => [
                        "id field is required"
                    ]]
                ]);
            }
            $user = $this->entityManager->getRepository(User::class)->find($id);
            if(!$user){
                return new JsonResponse([
                    'status' => 204,
                    'msg' => "User does not exist.",
                    'data' => []
                ]);
            }
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $comment = $request->request->get('comment');
            $client_id = $request->request->get('client_id');
            // Check uniqueness of email and phone number
            if (isset($email) && $email !== $user->getEmail()) {
                $existingUserWithEmail = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUserWithEmail) {
                    return new JsonResponse([
                        'status' => 422,
                        'msg' => 'There is some validation error!',
                        'error' => ['email' => [
                            'Email already exists'
                        ]]
                    ]);
                }
            }
            $constraints = [];
            if (isset($name)) {
                $constraints['name'] = [
                    new NotBlank([
                        'message' => 'Please enter your name',
                    ]),
                ];
            }

            if (isset($email)) {
                $constraints['email'] = [
                    new NotBlank([
                        'message' => 'Please enter your email',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email',
                    ]),
                ];
            }

            if (isset($phone)) {
                $constraints['phone'] = [
                    new Optional([
                        new Regex([
                            'pattern' => '/^\d{10}$/',
                            'message' => 'Phone number should be a 10-digit number',
                        ]),
                    ]),
                ];
            }

            if (isset($comment)) {
                $constraints['comment'] = [
                    new NotBlank([
                        'message' => 'Please enter your comment',
                    ]),
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Your comment must contain a maximum of {{ limit }} characters',
                    ]),
                ];
            }

            if (isset($client_id)) {
                $constraints['client_id'] = [
                    new NotBlank([
                        'message' => 'Please enter your client_id',
                    ]),
                ];
            }

            $constraint = new Assert\Collection($constraints);
            // Get all the request data
            $data = $request->request->all();
            // Exclude the 'id' field from validation
            unset($data['id']);
            $errors = $validator->validate(
                $data,
                $constraint
            );
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[$violation->getPropertyPath()][] = $violation->getMessage();
                }
                return new JsonResponse([
                    'status' => 422,
                    'msg' => "There is some validation error!",
                    'error' => $messages
                ]);
            }
            $user->setName($name ?? $user->getName());
            $user->setEmail($email ?? $user->getEmail());
            $user->setPhone($phone ?? $user->getPhone());
            $user->setComment($comment ?? $user->getComment());
            $user->setClientId($client_id ?? $user->getClientId());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            // Serialize the entire entity (all fields)
            $userData = $serializer->normalize($user);
            return new JsonResponse([
                'status'=> 200,
                'msg' => "User detail updated  successfully!",
                'data' => $userData,
            ]);
        }catch (\Exception $e){
            return new JsonResponse([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
    }
    #[Route('/api/user/delete/{id}', name: 'app_api_user_delete')]
    public function delete($id):JsonResponse
    {
        try{
            $user = $this->entityManager->getRepository(User::class)->find($id);
            if(!$user){
                return new JsonResponse([
                    'status' => 204,
                    'msg' => "User does not exist.",
                    'data' => []
                ]);
            }
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return new JsonResponse([
                'status'=> 200,
                'msg' => "User deleted successfully",
                'data' => [],
            ]);
        }catch (\Exception $e){
            return new JsonResponse([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
