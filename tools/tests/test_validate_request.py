import os
import sys
import unittest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from validate_request import validate_request


class TestValidateRequest(unittest.TestCase):
    def test_valid_submission_has_no_errors(self):
        errors = validate_request("Анна Новак", "anna@example.com", "Цікавить білий сфінкс.", True)
        self.assertEqual(errors, [])

    def test_short_name_is_rejected(self):
        errors = validate_request("Ан", "anna@example.com", "Цікавить білий сфінкс.", True)
        self.assertIn("Ім'я повинно бути більше двох символів.", errors)

    def test_invalid_email_is_rejected(self):
        errors = validate_request("Анна Новак", "not-an-email", "Цікавить білий сфінкс.", True)
        self.assertIn("Email заповнено некоректно.", errors)

    def test_short_nonempty_message_is_rejected(self):
        errors = validate_request("Анна Новак", "anna@example.com", "Привіт", True)
        self.assertIn("Повідомлення занадто коротке.", errors)

    def test_empty_message_is_allowed(self):
        # api.php only rejects a message that's short AND non-empty --
        # an empty message is allowed (the form falls back to a default).
        errors = validate_request("Анна Новак", "anna@example.com", "", True)
        self.assertEqual(errors, [])

    def test_missing_consent_is_rejected(self):
        errors = validate_request("Анна Новак", "anna@example.com", "Цікавить білий сфінкс.", False)
        self.assertIn("Потрібна згода на обробку персональних даних.", errors)

    def test_multiple_errors_are_all_reported(self):
        errors = validate_request("Ан", "not-an-email", "Привіт", False)
        self.assertEqual(len(errors), 4)


if __name__ == "__main__":
    unittest.main()
