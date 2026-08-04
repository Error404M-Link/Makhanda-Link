module Main exposing (..)
import Html exposing (Html)

amount:number
amount = 10
 
greeting : Int -> String
greeting count = 
    "Welcome! you have " ++ String.fromInt count ++ " items in your shopping cart"

dayPromo : number -> String
dayPromo day =
    if day == 1 then
        " Prices are 30% off! "
    else 
        " Prces are 10% off! "   -- the else branch is mandatory in ELM

fruitPromo : a -> String
fruitPromo fruit = 
    case fruit of
        "Apple" -> 
            "Apples are on sale today"
        "banana" -> 
            "Great price for bananas"
        _->  -- DEFAULT CASE
            "Large discounts"

main = 
     Html.text (greeting 0)  -- the parenthesis force the greeting function to execute 1st
                             -- before being passed into the Html.text