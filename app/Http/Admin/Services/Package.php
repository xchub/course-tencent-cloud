<?php

namespace App\Http\Admin\Services;

use App\Library\Paginator\Query as PagerQuery;
use App\Models\CoursePackage as CoursePackageModel;
use App\Models\Package as PackageModel;
use App\Repos\Course as CourseRepo;
use App\Repos\CoursePackage as CoursePackageRepo;
use App\Repos\Package as PackageRepo;
use App\Validators\Package as PackageValidator;

class Package extends Service
{

    public function getPackages()
    {
        $pagerQuery = new PagerQuery();

        $params = $pagerQuery->getParams();

        $params['deleted'] = $params['deleted'] ?? 0;

        $sort = $pagerQuery->getSort();
        $page = $pagerQuery->getPage();
        $limit = $pagerQuery->getLimit();

        $pageRepo = new PackageRepo();

        $pager = $pageRepo->paginate($params, $sort, $page, $limit);

        return $pager;
    }

    public function getPackage($id)
    {
        $package = $this->findOrFail($id);

        return $package;
    }

    public function createPackage()
    {
        $post = $this->request->getPost();

        $validator = new PackageValidator();

        $data = [];

        $data['title'] = $validator->checkTitle($post['title']);
        $data['summary'] = $validator->checkSummary($post['summary']);

        $package = new PackageModel();

        $package->create($data);

        return $package;
    }

    public function updatePackage($id)
    {
        $package = $this->findOrFail($id);

        $post = $this->request->getPost();

        $validator = new PackageValidator();

        $data = [];

        if (isset($post['title'])) {
            $data['title'] = $validator->checkTitle($post['title']);
        }

        if (isset($post['summary'])) {
            $data['summary'] = $validator->checkSummary($post['summary']);
        }

        if (isset($post['market_price'])) {
            $data['market_price'] = $validator->checkMarketPrice($post['market_price']);
        }

        if (isset($post['vip_price'])) {
            $data['vip_price'] = $validator->checkVipPrice($post['vip_price']);
        }

        if (isset($post['published'])) {
            $data['published'] = $validator->checkPublishStatus($post['published']);
        }

        if (isset($post['xm_course_ids'])) {
            $this->saveCourses($package, $post['xm_course_ids']);
        }

        $package->update($data);

        $this->updateCourseCount($package);

        return $package;
    }

    public function deletePackage($id)
    {
        $package = $this->findOrFail($id);

        if ($package->deleted == 1) {
            return false;
        }

        $package->deleted = 1;

        $package->update();

        return $package;
    }

    public function restorePackage($id)
    {
        $package = $this->findOrFail($id);

        if ($package->deleted == 0) {
            return false;
        }

        $package->deleted = 0;

        $package->update();

        return $package;
    }

    public function getGuidingCourses($courseIds)
    {
        if (!$courseIds) return [];

        $courseRepo = new CourseRepo();

        $ids = explode(',', $courseIds);

        $courses = $courseRepo->findByIds($ids);

        return $courses;
    }

    public function getGuidingPrice($courses)
    {
        $totalMarketPrice = $totalVipPrice = 0;

        if ($courses) {
            foreach ($courses as $course) {
                $totalMarketPrice += $course->market_price;
                $totalVipPrice += $course->vip_price;
            }
        }

        $sgtMarketPrice = sprintf('%0.2f', intval($totalMarketPrice * 0.9));
        $sgtVipPrice = sprintf('%0.2f', intval($totalVipPrice * 0.8));

        $price = new \stdClass();
        $price->market_price = $sgtMarketPrice;
        $price->vip_price = $sgtVipPrice;

        return $price;
    }

    public function getXmCourses($id)
    {
        $packageRepo = new PackageRepo();

        $courses = $packageRepo->findCourses($id);

        $list = [];

        if ($courses->count() > 0) {
            foreach ($courses as $course) {
                $list[] = [
                    'id' => $course->id,
                    'title' => $course->title,
                    'selected' => true,
                ];
            }
        }

        return $list;
    }

    protected function saveCourses($package, $courseIds)
    {
        $packageRepo = new PackageRepo();

        $courses = $packageRepo->findCourses($package->id);

        $originCourseIds = [];

        if ($courses->count() > 0) {
            foreach ($courses as $course) {
                $originCourseIds[] = $course->id;
            }
        }

        $newCourseIds = explode(',', $courseIds);
        $addedCourseIds = array_diff($newCourseIds, $originCourseIds);

        if ($addedCourseIds) {
            foreach ($addedCourseIds as $courseId) {
                $coursePackage = new CoursePackageModel();
                $coursePackage->create([
                    'course_id' => $courseId,
                    'package_id' => $package->id,
                ]);
            }
        }

        $deletedCourseIds = array_diff($originCourseIds, $newCourseIds);

        if ($deletedCourseIds) {
            $coursePackageRepo = new CoursePackageRepo();
            foreach ($deletedCourseIds as $courseId) {
                $coursePackage = $coursePackageRepo->findCoursePackage($courseId, $package->id);
                if ($coursePackage) {
                    $coursePackage->delete();
                }
            }
        }
    }

    protected function updateCourseCount($package)
    {
        $packageRepo = new PackageRepo();

        $courseCount = $packageRepo->countCourses($package->id);

        $package->course_count = $courseCount;

        $package->update();
    }

    protected function findOrFail($id)
    {
        $validator = new PackageValidator();

        $result = $validator->checkPackage($id);

        return $result;
    }

}