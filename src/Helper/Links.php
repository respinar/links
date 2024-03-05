<?php

/*
 * This file is part of Contao Links Bundle.
 *
 * (c) Hamid Peywasti 2014-2024 <hamid@respinar.com>
 *
 * @license LGPL-3.0-or-later
 */

/**
 * Namespace
 */
namespace Respinar\LinksBundle\Helper;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Respinar\Links\Model\LinksCategoryModel;
use Respinar\Links\Model\LinksModel;

/**
 * Class Helper Links
 */
class Links
{
	/**
	 * URL cache array
	 * @var artray
	 */
	private static $arrUrlCache = array();

	/**
	 * Parse Link
	 */
	static public function parseLink(LinksModel $objLink, ModuleModel $model, int $intCount=0): string
	{

		$objTemplate = new FrontendTemplate($model->links_template);

		$objTemplate->setData($objLink->row());

		$objTemplate->addImage = false;

		// Add an image
		if ($objLink->singleSRC)
		{
			if ($model->imgSize)
			{
				$size = StringUtil::deserialize($model->imgSize);

				if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
				{
					$imgSize = $model->imgSize;
				}
			}

			$figure = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->setSize($imgSize)
				->from($objLink->singleSRC)
                ->buildIfResourceExists();

			$figure->applyLegacyTemplateData($objTemplate);
		}
		
		$objTemplate->hrefclass = $objLink->class;
		$objTemplate->linkTitle = $objLink->linkTitle ? $objLink->linkTitle : $objLink->title;

		return $objTemplate->parse();
	}

	/**
	 * Pars Links
	 * 
	 * @return array<string>
	 * 
	 */
	static public function parseLinks(Collection $objLinks, ModuleModel $model): array
	{
		$limit = $objLinks->count();

		if ($limit < 1)
		{
			return array();
		}

		$count = 0;
		$arrLinks = array();

		foreach ($objLinks as $objLink) {
			$arrLinks[] = self::parseLink($objLink, $model);
		}

		return $arrLinks;
	}

	/**
	 * Sort out protected categories
	 */
	public static function sortOutProtected(array $arrCategories): array
	{
		if (empty($arrCategories) || !\is_array($arrCategories))
		{
			return $arrCategories;
		}

		$objCategory = LinksCategoryModel::findMultipleByIds($arrCategories);
		$arrCategories = array();

		if ($objCategory !== null)
		{
			$security = System::getContainer()->get('security.helper');

			while ($objCategory->next())
			{
				if ($objCategory->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objCategory->groups))
				{
					continue;
				}

				$arrCategories[] = $objCategory->id;
			}
		}

		return $arrCategories;
	}
}
