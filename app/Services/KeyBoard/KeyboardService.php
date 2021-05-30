<?php


namespace App\Services\KeyBoard;


use App\Services\Keyboard\Abstracts\KeyboardInterface;

/**
 * Class KeyboardService
 * @package App\Services\KeyBoard
 */
class KeyboardService implements KeyboardInterface
{
    /**
     * @return false|mixed|string
     */
    public function getMainKeyboard()
    {
        return json_encode([
            "keyboard" => [
                // первый ряд кнопок клавиатуры
                [
                    // первая кнопка первого ряда клавиатуры
                    [
                        "text" => "Мои КТ",
                    ],
                    // вторая кнопка первого ряда клавиатуры
                    [
                        "text" => "КТ",
                    ]
                ],
                // второй ряд клавиатуры
                [
                    // первая кнопка второго ряда клавиатуры
                    [
                        "text" => "Предметы",
                    ],
                    // вторая кнопка второго ряда клавиатуры
                    [
                        "text" => "Профиль",
                    ]
                ],
                // третий ряд клавиатуры
                [
                    // первая кнопка третьего ряда клавиатуры
                    [
                        "text" => "Помощь",
                    ],
                    // третья кнопка второго ряда клавиатуры
                    [
                        "text" => "Назад",
                    ]
                ]
            ],
            'one_time_keyboard' => false,
            'resize_keyboard' => true,
            'selective' => true,
        ], true);
    }

    /**
     * @return false|mixed|string
     */
    public function getKeyboardYesOrNo()
    {
        return json_encode([
            "keyboard" => [
                [
                    [
                        "text" => "Нет",
                    ],

                    [
                        "text" => "Да",
                    ]
                ],
            ],
            'one_time_keyboard' => false,
            'resize_keyboard' => true,
            'selective' => true,
        ], true);
    }

    /**
     * @return mixed
     */
    public function getProfileKeyboard()
    {
        return json_encode([
            "keyboard" => [
                // первый ряд кнопок клавиатуры
                [
                    // первая кнопка первого ряда клавиатуры
                    [
                        "text" => "Изменить время помидора",
                    ],
                ],
                // второй ряд клавиатуры
                [
                    // первая кнопка второго ряда клавиатуры
                    [
                        "text" => "Изменить номер зачетки",
                    ],
                ],
                // третий ряд клавиатуры
                [
                    // третья кнопка второго ряда клавиатуры
                    [
                        "text" => "Назад",
                    ]
                ]

            ],
            'one_time_keyboard' => false,
            'resize_keyboard' => true,
            'selective' => true,
        ], true);
    }
}
