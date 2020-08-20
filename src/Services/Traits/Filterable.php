<?php

namespace Ushahidi\Gmail\Services\Traits;

use Ushahidi\Gmail\Services\Mailbox;

/**
 * Adapted from https://github.com/dacastro4/laravel-gmail/blob/master/src/Traits/Filterable.php
 */
trait Filterable
{
	public abstract function filter($query);

	/**
	 * Filter to get only unread emalis
	 *
	 * @return self|Mailbox
	 */
	public function isUnread()
	{
		$this->filter('is:unread');

		return $this;
	}

	/**
	 * Filter to get only unread emalis
	 *
	 * @param string $query
	 *
	 * @return self|Mailbox
	 */
	public function subject($query)
	{
		$this->filter("[{$query}]");

		return $this;
	}

	/**
	 * Filter to get only emails from a specific email address
	 *
	 * @param string $email
	 *
	 * @return self|Mailbox
	 */
	public function to($email)
	{
		$this->filter("to:{$email}");

		return $this;
	}

	/**
	 * add an array of from addresses
	 *
	 * @param array $emails
	 *
	 * @return self|Mailbox
	 */
	public function fromThese(array $emails)
	{
		$emailsCount = count($emails);
		for ($i = 0; $i < $emailsCount; $i++) {
			!$i ? $this->filter("{from:$emails[$i]") : ($i == $emailsCount - 1 ? $this->filter("from:$emails[$i]}") : $this->from($emails[$i]));
		}

		return $this;
	}

	/**
	 * Filter to get only emails from a specific email address
	 *
	 * @param string $email
	 *
	 * @return self|Mailbox
	 */
	public function from($email)
	{
		$this->filter("from:{$email}");

		return $this;
	}

	/**
	 * Filter to get only emails after a specific date
	 *
	 * @param string $date
	 *
	 * @return self|Mailbox
	 */
	public function after($date)
	{
		$this->filter("after:{$date}");

		return $this;
	}

	/**
	 * Filter to get only emails before a specific date
	 *
	 * @param string $date
	 *
	 * @return self|Mailbox
	 */
	public function before($date)
	{
		$this->filter("before:{$date}");

		return $this;
	}

	/**
	 * Filters emails by tag
	 * 
	 * Example:
	 * * starred
	 * * inbox
	 * * spam
	 * * chats
	 * * sent
	 * * draft
	 * * trash
	 *
	 * @param string $box
	 *
	 * @return self|Mailbox
	 */
	public function in($box = 'inbox')
	{
		$this->filter("in:{$box}");

		return $this;
	}

	/**
	 * Determines if the email has attachments
	 *
	 * @return self|Mailbox
	 */
	public function hasAttachment()
	{
		$this->filter('has:attachment');

		return $this;
	}
}
