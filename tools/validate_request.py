"""Reference implementation of the contact-form validation rules used in
site/api.php (and site/create_request.php / edit_request.php).

PHP isn't installed in every environment that might check out this repo,
so this module mirrors the validation logic in Python, where it can be
unit-tested without a PHP runtime or a database. It is not imported by the
PHP site at runtime -- it exists purely so the validation rules have an
automated, language-independent check.
"""

import re

EMAIL_RE = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")


def validate_request(name: str, email: str, message: str, consent: bool) -> list[str]:
    """Mirrors the checks in api.php. Returns a list of error messages
    (empty list means the submission is valid)."""
    errors = []

    if len(name.strip()) <= 2:
        errors.append("Ім'я повинно бути більше двох символів.")

    if not EMAIL_RE.match(email.strip()):
        errors.append("Email заповнено некоректно.")

    message = message.strip()
    if message != "" and len(message) <= 10:
        errors.append("Повідомлення занадто коротке.")

    if not consent:
        errors.append("Потрібна згода на обробку персональних даних.")

    return errors


if __name__ == "__main__":
    examples = [
        ("Анна Новак", "anna.novak@example.com", "Цікавить білий сфінкс, прошу зв'язатися.", True),
        ("Ан", "anna.novak@example.com", "Цікавить білий сфінкс.", True),
        ("Анна Новак", "not-an-email", "Цікавить білий сфінкс.", True),
        ("Анна Новак", "anna.novak@example.com", "Привіт", True),
        ("Анна Новак", "anna.novak@example.com", "Цікавить білий сфінкс.", False),
    ]
    for name, email, message, consent in examples:
        errors = validate_request(name, email, message, consent)
        status = "OK" if not errors else "ERRORS: " + "; ".join(errors)
        print(f"{name!r:25} {email!r:30} consent={consent!s:5} -> {status}")
