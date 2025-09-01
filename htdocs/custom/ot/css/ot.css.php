<?php
header('Content-type: text/css');
header('Cache-Control: max-age=10800, public, must-revalidate');
?>

body {
    font-family: Arial, sans-serif;
    color: #333;
    margin: 0;
    padding: 0;
}

.mod-ot .card {
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease-in-out;
    margin-bottom: 20px;
    width: 100%;
    height: auto;
    position: relative;
    display: block;
}

.mod-ot .card-header {
    background-color: #2F508B;
    color: #fff;
    padding: 10px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    font-size: 1.2em;
    font-weight: bold;
    text-align: center;
}

.mod-ot .card-body {
    padding: 15px;
    text-align: center;
}

.mod-ot .btn {
    display: inline-block;
    padding: 8px 12px;
    font-size: 14px;
    color: #fff;
    text-align: center;
    font-weight: bold;
    background-color: #28508b;
    border-radius: 4px;
    margin-top: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: background-color 0.3s;
    border: 1px solid transparent;
}

.mod-ot .btn-info {
    background-color: #3c9613;
}

.mod-ot .btn-secondary {
    background-color: #28508b;
}

.mod-ot .btn-danger {
    background-color: #dc3545;
}

.mod-ot .btn:hover {
    background-color: #218838;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.mod-ot .btn-info:hover {
    background-color: #3c9613;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.mod-ot .btn-secondary:hover {
    background-color: #28508b;
    transform: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.mod-ot .btn-danger:hover {
    background-color: #c82333;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.mod-ot .card-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 5px;
    width: 100%;
}

.mod-ot .card-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 350px;
    flex-grow: 1;
}

.mod-ot .card-column .card {
    margin: 10px 0;
}

.mod-ot .dropdown {
    position: relative;
    display: inline-block;
}

.mod-ot .dropdown-content {
    display: none;
    position: absolute;
    background-color: #fff;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 1;
    left: 50%;
    transform: translateX(-50%);
    top: 100%;
    margin-top: 1px;
}

.mod-ot .dropdown-content button {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    width: 100%;
    text-align: left;
    border: none;
    background: #fff;
    cursor: pointer;
    font-weight: bold;
    margin: 0;
}

.mod-ot .dropdown-content button:hover {
    background-color: #ddd;
}

.mod-ot .dropdown:hover .dropdown-content {
    display: block;
}

.mod-ot .delete-button {
    background-color: #dc3545;
    border: none;
    color: #fff;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin: 10px auto;
    display: block;
}

.mod-ot .form-label {
    display: block;
    font-weight: bold;
    margin-top: 10px;
}

.mod-ot select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.mod-ot .contact-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
    font-size: 14px;
    background: #f1f1f1;
    padding: 8px;
    border-radius: 4px;
}

.mod-ot .contact-info input {
    width: 100px;
    padding: 5px;
    font-size: 12px;
    border-radius: 4px;
    border: 1px solid #ccc;
}

.mod-ot .supplier-section {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    padding: 20px;
}

.mod-ot .cardsoustraitant {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    width: 100%;
    padding: 20px;
    text-align: center;
}

.mod-ot .card-header-soustraitant {
    transition: transform 0.2s ease-in-out;
    margin-bottom: 20px;
    width: 100%;
    height: auto;
    position: relative;
    display: block;
}

.mod-ot .cardsoustraitant label {
    display: block;
    font-weight: bold;
    margin: 10px 0 5px;
    text-align: left;
}

.mod-ot .cardsoustraitant select {
    width: 100%;
    padding: 8px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    background: #f9f9f9;
}

.mod-ot .contact-container {
    display: none;
    margin-top: 15px;
}

.mod-ot .cardsoustraitant .card-body {
    display: table;
    width: 100%;
    background: #f8f9fa;
    border-radius: 5px;
    border: 1px solid #ccc;
    padding: 10px;
}

.mod-ot .cardsoustraitant .data-row {
    display: table-row;
    text-align: center;
    padding: 5px 0;
}

.mod-ot input:disabled, .mod-ot select:disabled, .mod-ot textarea:disabled {
    background-color: white !important;
    color: #333 !important;
    border: none;
    cursor: default;
    box-shadow: none;
}

.mod-ot .list-title-input {
    font-size: 16px;
    font-weight: bold;
    text-align: center;
    color: #333;
    margin-top: 8px;
}
.mod-ot .card .title-input {
    font-size: 16px;
    font-weight: bold;
    text-align: center;
    color: #333;
}

.mod-ot .cardsoustraitant .legend-row {
    display: flex;
    justify-content: space-between;
    text-align: center;
    padding: 5px 0;
    font-weight: bold;
    width: 100%;
    box-sizing: border-box;
}

.mod-ot .cardsoustraitant .form-input {
    font-size: 15px;
    padding: 5px;
    border-radius: 4px;
    box-sizing: border-box;
}

.mod-ot .cardsoustraitant .data-row > div {
    flex: 1;
    padding: 0 10px;
    text-align: center;
    box-sizing: border-box;
}

/* --- Fin des styles de ot_card.php --- */


