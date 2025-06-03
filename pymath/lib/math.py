def is_palindrome(s):
  """
  Checks if a string is a palindrome.

  A palindrome is a word, phrase, number, or other sequence of characters
  that reads the same backward as forward, such as madam or racecar.
  """
  if not isinstance(s, str):
    raise TypeError("Input must be a string.")
  s = ''.join(filter(str.isalnum, s)).lower()
  return s == s[::-1]

def factorial(n):
  """Calculates the factorial of a non-negative integer n."""
  if n < 0:
    raise ValueError("Factorial is not defined for negative numbers")
  elif n == 0:
    return 1
  else:
    return n * factorial(n - 1)


def fibonacci(n):
  """Calculates the nth Fibonacci number."""
  if n < 0:
    raise ValueError("Fibonacci sequence is not defined for negative numbers")
  elif n == 0:
    return 0
  elif n == 1:
    return 1 # Corrected fibonacci(1) to return 1
  else:
    return fibonacci(n - 1) + fibonacci(n - 2)


def gcd(a, b):
  """Calculates the Greatest Common Divisor (GCD) of two integers."""
  while b:
    a, b = b, a % b
  return a


def lcm(a, b):
  """Calculates the Least Common Multiple (LCM) of two integers"""
  return (a * b) // gcd(a, b)


def is_perfect_square(n):
  """Checks if a number is a perfect square."""
  if n >= 0:
      root = int(n**0.5)
      return root * root == n
  return False

def is_prime(n):
  """Checks if a number is a prime number."""
  if n <= 1:
    return False
  for i in range(2, int(n**0.5) + 1):
    if n % i == 0:
      return False
  return True
