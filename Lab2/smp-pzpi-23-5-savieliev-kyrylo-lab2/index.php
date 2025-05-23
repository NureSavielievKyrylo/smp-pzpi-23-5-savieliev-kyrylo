<?php

class Restrictions {
	public static int $MIN_AGE = 7;
	public static int $MAX_AGE = 150;
}

function prettifyGoodName(string $name): string {
	$pad_length = 20 - mb_strlen($name);

	if ($pad_length > 0) {
		$pad = str_repeat(" ", $pad_length);
		return $name . $pad;
	}

	return $name;
}

class GoodsCatalog {
	private array $goods;

	public function __construct() {
		$this->goods = $this->loadGoods();
	}

	public function getGoods(): array {
		return $this->goods;
	}

	public function getGoodById(int $id) {
		return array_find($this->goods, fn($good) => $good["id"] == $id);
	}

	public function getGoodByName(string $name) {
		return array_find($this->goods, fn($good) => $good["name"] == $name);
	}

	private function loadGoods(): array {
		$jsonString=file_get_contents("./data/goods.json");

		return json_decode($jsonString, true);
	}
}

class Cart {
	private array $items = [];

	public function isEmpty(): bool {
		return empty($this->items);
	}

	public function getItems(): array {
		return $this->items;
	}

	public function addItem(string $name, int $quantity): void {
		if ($quantity < 0) {
			return;
		}

		if ($quantity == 0) {
			$this->removeItem($name);
		} else {
			$this->items[$name] = $quantity;
		}
	}

	public function removeItem(string $name): void {
		unset($this->items[$name]);
	}

	public function calculateTotal(GoodsCatalog $catalog): int {
		$total = 0;

		foreach ($this->items as $name => $quantity) {
			$good = $catalog->getGoodByName($name);

			if ($good) {
				$total += $good["price"] * $quantity;
			}
		}

		return $total;
	}
}

class User {
	private string $name = "";
	private int $age = 0;

	public function setName(string $name): void {
		$this->name = $name;
	}

	public function setAge(int $age): void {
		$this->age = $age;
	}
}

class App {
	private GoodsCatalog $catalog;
	private Cart $cart;
	private User $user;

	public function __construct() {
		$this->catalog = new GoodsCatalog();
		$this->cart = new Cart();
		$this->user = new User();
	}

	private function renderMainMenu(): void {
		echo "\n################################\n";
		echo "# ПРОДОВОЛЬЧИЙ МАГАЗИН \"ВЕСНА\" #\n";
		echo "################################\n";
		echo "1 Вибрати товари\n";
		echo "2 Отримати підсумковий рахунок\n";
		echo "3 Налаштувати свій профіль\n";
		echo "0 Вийти з програми\n";
		echo "Введіть команду: ";
	}

	public function run(): void {
		while (true) {
			$this->renderMainMenu();

			$prompt = $this->getUserInput();

			switch ($prompt) {
				case "1":
					$this->handleGoodSelection();
					break;
				case "2":
					$this->renderTotal();
					break;
				case "3":
					$this->setupProfile();
					break;
				case "0":
					echo "Дякуємо за покупку в магазині \"Весна\"! До побачення!\n";
					exit;
				default:
					echo "ПОМИЛКА! Введіть правильну команду\n";
					break;
			}
		}
	}

	private function renderGoodsList(): void {
		$goods = $this->catalog->getGoods();

		echo "№  НАЗВА                   ЦІНА\n";

		foreach ($goods as $good) {
			printf("%-2s %-24s %5s\n", $good["id"], prettifyGoodName($good["name"]), $good["price"]);
		}

		echo "   -----------\n";
		echo "0  ПОВЕРНУТИСЯ\n";
		echo "Виберіть товар: ";
	}

	private function handleGoodSelection(): void {
		while (true) {
			$this->renderGoodsList();

			$selectedId = $this->getUserInput();

			if ($selectedId == "0") {
				break;
			}

			$good = $this->catalog->getGoodById($selectedId);

			if (!$good) {
				echo "ПОМИЛКА! ВКАЗАНО НЕПРАВИЛЬНИЙ НОМЕР ТОВАРУ\n";
				continue;
			}

			echo "Вибрано: {$good["name"]}\n";
			echo "Введіть кількість, штук: ";
			$quantityInput = $this->getUserInput();

			if (!ctype_digit($quantityInput)) {
				echo "ПОМИЛКА! Кількість повинна бути цілим невід'ємним числом.\n";
				continue;
			}

			$this->cart->addItem($good["name"], $quantityInput);

			$this->renderCart();
		}
	}

	private function renderTotal(): void {
		if ($this->cart->isEmpty()) {
			echo "КОШИK ПОРОЖНІЙ\n";
			return;
		}

		$cartItems = $this->cart->getItems();

		$i = 1;

		echo "№  НАЗВА                   ЦІНА   КІЛЬКІСТЬ  ВАРТІСТЬ\n";
		echo "---------------------------------\n";
		foreach ($cartItems as $name => $qty) {
			$good = $this->catalog->getGoodByName($name);
			$sum = $good["price"] * $qty;

			printf("%-2s %-24s %6s %9s %9s\n", $i++,  prettifyGoodName($name), $good["price"], $qty, $sum);
		}
		echo "---------------------------------\n";

		$total = $this->cart->calculateTotal($this->catalog);
		printf("РАЗОМ ДО СПЛАТИ: %s\n", $total);
	}

	private function renderCart(): void {
		if ($this->cart->isEmpty()) {
			echo "КОШИK ПОРОЖНІЙ\n";
			return;
		}

		$cartItems = $this->cart->getItems();


		echo "У КОШИКУ:\nНАЗВА                   КІЛЬКІСТЬ\n";
		echo "---------------------------------\n";
		foreach ($cartItems as $name => $qty) {
			$good = $this->catalog->getGoodByName($name);
			$sum = $good["price"] * $qty;

			printf("%-24s %5s\n",  prettifyGoodName($name), $qty);
		}
		echo "---------------------------------\n";
	}

	private function setupProfile(): void {
		do {
			echo "Ваше імʼя: ";

			$userName = $this->getUserInput();;
		} while (!preg_match("/^\p{L}+$/u", $userName));

		$this->user->setName($userName);

		do {
			echo "Ваш вік: ";

			$userAge = $this->getUserInput();
			$inRange = $userAge >= Restrictions::$MIN_AGE && $userAge <= Restrictions::$MAX_AGE;
		} while (!preg_match("/^\d+$/u", $userAge) || !$inRange);

		$this->user->setAge($userAge);
	}

	private function getUserInput(): string
	{
		$input = fgets(STDIN);

		return $input ? trim($input) : "";
	}
}

$app = new App();

$app->run();
