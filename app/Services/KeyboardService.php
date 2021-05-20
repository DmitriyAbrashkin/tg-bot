<?php


namespace App\Services;


class KeyboardService
{
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
                        "text" => "Статистика",
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
}
