import unittest
from pymath.lib.math import (
    factorial,
    fibonacci,
    is_prime,
    gcd,
    lcm,
    is_perfect_square,
    is_palindrome
)

class TestMathFunctions(unittest.TestCase):

    def test_factorial(self):
        self.assertEqual(factorial(5), 120)
        self.assertEqual(factorial(0), 1)
        with self.assertRaises(ValueError):
            factorial(-1)

    def test_fibonacci(self):
        self.assertEqual(fibonacci(0), 0)
        self.assertEqual(fibonacci(1), 1) # Corrected expected value for fibonacci(1)
        self.assertEqual(fibonacci(10), 55) # Corrected expected value for fibonacci(10)
        with self.assertRaises(ValueError):
            fibonacci(-1)

    def test_is_prime(self):
        self.assertTrue(is_prime(2))
        self.assertTrue(is_prime(3))
        self.assertFalse(is_prime(4))
        self.assertTrue(is_prime(13))
        self.assertFalse(is_prime(1))
        self.assertFalse(is_prime(0))
        self.assertFalse(is_prime(-1))

    def test_gcd(self):
        self.assertEqual(gcd(48, 18), 6)
        self.assertEqual(gcd(101, 13), 1)

    def test_lcm(self):
        self.assertEqual(lcm(12, 18), 36)
        self.assertEqual(lcm(7, 5), 35)

    def test_is_perfect_square(self):
        self.assertTrue(is_perfect_square(9))
        self.assertTrue(is_perfect_square(0))
        self.assertFalse(is_perfect_square(10))
        self.assertFalse(is_perfect_square(-4))

    def test_is_palindrome(self):
        self.assertTrue(is_palindrome("racecar"))
        self.assertTrue(is_palindrome("A man, a plan, a canal: Panama"))
        self.assertTrue(is_palindrome("Was it a car or a cat I saw?"))
        self.assertFalse(is_palindrome("hello"))
        self.assertTrue(is_palindrome(""))  # Empty string is a palindrome
        self.assertTrue(is_palindrome("madam"))
        # Test with numbers as strings
        self.assertTrue(is_palindrome("121"))
        self.assertFalse(is_palindrome("123"))
        # Test with mixed case
        self.assertTrue(is_palindrome("Level"))
        with self.assertRaises(TypeError):
            is_palindrome(121) # Test non-string input

if __name__ == '__main__':
    unittest.main()
