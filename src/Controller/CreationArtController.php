<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;

class CreationArtController extends AbstractController
{

	const RECAPTCHA_URL = "https://www.google.com/recaptcha/api/siteverify";

	const CATEGORIES = array(
		"allestimenti-negozi" => "Allestimenti di Negozi",
		"scenografie"         => "Scenografie",
		"grafica"             => "Grafica",
		"insegne"             => "Insegne",
		"murales"             => "Murales",
		"barche"              => "Barche",
		"aerografie"          => "Aerografie",
		"tecnologia"          => "Tecnologia",
		"abbigliamento"       => "Abbigliamento",
		"oggettistica"        => "Oggettistica",
		"arredamento"         => "Arredamento",
		"camini"              => "Camini",
		"affreschi"           => "Affreschi",
		"decori"              => "Decori",
		"ritratti"            => "Ritratti",
		"trompe-loeil"        => "Trompe l'oeil",
		"vetrofanie"          => "Vetrofanie"
	);

	/**
	 * @Route("/", name="home")
	 */
	public function home()
	{
		return $this->render("home.html.twig", array("categories" => self::CATEGORIES));
	}

	/**
	 * @Route("/categoria", name="category")
	 */
	public function category(Request $request)
	{
		$category = $request->query->get("category");

		$relativeGalleryDir = sprintf(
			"images/categories/%s/gallery",
			$category
		);

		$absoluteGalleryDir = sprintf(
			"%s/public/%s",
			$GLOBALS["kernel"]->getProjectDir(),
			$relativeGalleryDir
		);

		$finder = new Finder();
		$finder->files()->name("*.jpg")->in($absoluteGalleryDir);

		$gallery = array();
		foreach ($finder as $file) {
			$image = sprintf(
				"/%s/%s",
				$relativeGalleryDir,
				$file->getRelativePathname()
			);

			$gallery[] = $image;
		}

		return $this->render(
			"category.html.twig",
			array(
				"category" => self::CATEGORIES[$category],
				"gallery"  => $gallery
			)
		);
	}

	/**
	 * @Route("/artista", name="artist")
	 */
	public function artist()
	{
		return $this->render("artist.html.twig");
	}

	/**
	 * @Route("/contatti", name="contacts")
	 */
	public function contacts()
	{
		return $this->render(
			"contacts.html.twig",
			array("recaptcha_key" => getenv("RECAPTCHA_CLIENT_KEY"))
		);
	}

	/**
	 * @Route("/contattami", name="contact-me")
	 */
	public function contactMe(Request $request, \Swift_Mailer $mailer)
	{
		if ($request->isMethod("POST")) {
			$recaptcha = $request->get("g-recaptcha-response");

			$return = $this->getCaptcha($recaptcha);

			// recaptcha validation
			if ($return->success && $return->score > 0.5) {
				$validator = Validation::createValidator();

				// name validation
				$name       = trim($request->get("name"));
				$violations = $validator->validate(
					$name,
					array(
						new NotBlank(array("message" => "Si prega di inserire il nome")),
						new Length(
							array(
								"min"        => 3,
								"max"        => 50,
								"minMessage" => "Il nome dev'essere di almeno 3 caratteri",
								"maxMessage" => "Il nome dev'essere non può essere più lungo di 50 caratteri"
							)
						)
					)
				);

				if (0 !== count($violations)) {
					return new JsonResponse(
						array(
							"status" => 0,
							"name"   => $violation[0]->getMessage()
						)
					);
				}

				// email validation
				$email      = trim($request->get("email"));
				$violations = $validator->validate(
					$email,
					array(
						new NotBlank(array("message" => "Si prega di inserire l'email")),
						new Email(
							array(
								"mode"    => "strict",
								"message" => "L'email inserita non è un'email valida"
							)
						)
					)
				);

				if (0 !== count($violations)) {
					return new JsonResponse(
						array(
							"status" => 0,
							"email"  => $violation[0]->getMessage()
						)
					);
				}

				// message validation
				$message    = trim($request->get("message"));
				$violations = $validator->validate(
					$message,
					array(
						new NotBlank(array("message" => "Si prega di scrivere il messaggio")),
						new Length(
							array(
								"min"        => 5,
								"max"        => 250,
								"minMessage" => "Il messaggio dev'essere di almeno 5 caratteri",
								"maxMessage" => "Il messaggio dev'essere non può essere più lungo di 250 caratteri"
							)
						)
					)
				);

				if (0 !== count($violations)) {
					return new JsonResponse(
						array(
							"status"  => 0,
							"message" => $violation[0]->getMessage()
						)
					);
				}

				// send email
				$message = (new \Swift_Message("Form CONTATTI di Creation Art"))
					->setFrom(getenv("MAIL_FROM"))
					->setTo(getenv("MAIL_TO"))
					->setBody(
						$this->renderView(
							"contactme.html.twig",
							array(
								"name"    => $name,
								"email"   => $email,
								"message" => $message
							)
						),
						"text/html"
					)
				;

				$mailer->send($message);

				return new JsonResponse(array("status" => 1));
			}
		}

		return new JsonResponse(array("status" => 0, "message" => "Oops... errore mio! Riprova per favore"));
	}

	private function getCaptcha($secretKey)
	{
		$query = http_build_query(
			array("secret" => getenv("RECAPTCHA_SERVER_KEY"), "response" => $secretKey)
		);

		$response = file_get_contents(self::RECAPTCHA_URL . "?" . $query);

		return json_decode($response);
	}

}
